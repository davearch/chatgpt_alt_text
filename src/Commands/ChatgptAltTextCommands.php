<?php

namespace Drupal\chatgpt_alt_text\Commands;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drush\Commands\DrushCommands;

class ChatgptAltTextCommands extends DrushCommands {

  /**
   * Generate alt text for images.
   *
   * @param int|null $file_id
   * The file id.
   *
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @command chatgpt-alt-text:generate
   * @aliases catgpt-alt-text-gen
   * @usage chatgpt-alt-text:generate 1
   */
  public function generateAltText(int $file_id = NULL): void {
    $prompt = 'Please provide alt text with this amazing prompt.';
    $provider_plugin_manager = \Drupal::service('ai.provider');
    $files = \Drupal::entityTypeManager()->getStorage('file')->load($file_id);
    $image = new ImageFile();
    $image->setFileFromFile($files);
    $input = new ChatInput([
      new ChatMessage('user',
        $prompt,
        [$image]
      ),
    ]);
    $provider = $provider_plugin_manager->createInstance('openai');
    $output = $provider->chat($input, 'gpt-4o');
    $this->writeToFile($file_id, $output->getNormalized()->getText());
  }

  /**
   * Write the alt text to a csv file.
   */
  private function writeToFile(int $file_id, string $output) : void {
    $file = fopen('alt_text.csv', 'a');
    fputcsv($file, [$file_id, $output]);
    fclose($file);
  }
}
