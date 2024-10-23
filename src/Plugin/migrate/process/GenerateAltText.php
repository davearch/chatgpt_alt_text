<?php

namespace Drupal\chatgpt_alt_text\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Config\ImmutableConfig;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\GenericType\ImageFile;


/**
 * @MigrateProcessPlugin(
 *  id = "generate_alt_text"
 * )
 */
class GenerateAltText extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The AI config.
   *
   * @var ImmutableConfig
   */
  private ImmutableConfig $aiConfig;

  /**
   * The AI provider.
   *
   * @var AiProviderPluginManager
   */
  private AiProviderPluginManager $aiProvider;


  /**
   * Constructor.
   *
   * @param array $configuration
   *  The configuration.
   * @param string $plugin_id
   *  The plugin id.
   * @param mixed $plugin_definition
   *  The plugin definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *  The entity type manager.
   * @param ImmutableConfig $ai_config
   *  The AI config.
   * @param AiProviderPluginManager $ai_provider
   *  The AI provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ImmutableConfig $ai_config, AiProviderPluginManager $ai_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->aiConfig = $ai_config;
    $this->aiProvider = $ai_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) : string {
    $prompt = "You are the world's foremost expert in alternative texts for images for accessibility. You want to generate the best possible alternative text for the given image. Please keep it 100 characters or less.";
    $file = $this->entityTypeManager->getStorage('file')->load($value);
    $image = new ImageFile();
    $image->setFileFromFile($file);
    $images = [$image];
    $input = new ChatInput([
      new ChatMessage('user',
        $prompt,
        $images
      ),
    ]);
    $default_provider = $this->aiProvider->getDefaultProviderForOperationType('chat_with_image_vision');
    // $default_provider['provider_id'] == 'openai';
    // $default_provider['model_id'] == 'gpt-4o';
    $provider = $this->aiProvider->createInstance($default_provider['provider_id']);
    $model = $default_provider['model_id'];
    $output = $provider->chat($input, $model);
    return $output->getNormalized()->getText();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory')->get('ai.settings'),
      $container->get('ai.provider')
    );

  }

}
