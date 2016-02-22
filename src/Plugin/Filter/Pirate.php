<?php
/**
 * @file
 * Contains \Drupal\pirate\Plugin\Filter\Pirate.
 */

namespace Drupal\pirate\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to change text into Pirate-speak
 *
 * @Filter(
 *   id = "pirate",
 *   module = "pirate",
 *   title = @Translation("Change text to  Pirate-speak"),
 *   description = @Translation("Ah, Squiddy! I got nothing against ye. I just heard there was gold in yer belly. Ha ha har, ha ha ha har!"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "pirate_display_tip" = 0,
 *   },
 *   weight = -10
 * )
 */
class Pirate extends FilterBase {
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings['pirate_display_tip'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display Pirate filter tip'),
      '#default_value' => isset($this->settings['pirate_display_tip']) ? $this->settings['pirate_display_tip'] : $defaults['pirate_display_tip'],
      '#description' => t('In case you want to make it a surprise on September 19th that pirates have taken over your site.'),
    );
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (\Drupal::service('date.formatter')->format(REQUEST_TIME, 'custom', 'md') != '0919') {
      return new FilterProcessResult($text);
    }
    // Allow others to alter our patterns.
    // Cache must be flushed for invocations of this hook to be felt.
    $patterns = array();
    $patterns = \Drupal::service('pirate.hook.captain')->invoke($patterns);
    // Most of the following code is taken from Drupal core's Filter module
    // in order to exclude text within tags, such as URLs that might get
    // modified using the replacement patterns.
    $ignore_tags = 'a|script|style|code|pre';
    $open_tag = '';
    $chunks = preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($patterns as $pattern_search => $pattern_replace) {
      $chunk_type = 'text';
      for ($i = 0; $i < count($chunks); $i++) {
        if ($chunk_type == 'text') {
          // Only process this text if there are no unclosed $ignore_tags.
          if ($open_tag == '') {
            // If there is a match, replace this in the chunk
            $chunks[$i] = preg_replace($pattern_search, $pattern_replace, $chunks[$i]);
          }
          // Text chunk is done, so next chunk must be a tag.
          $chunk_type = 'tag';
        }
        else {
          // Only process this tag if there are no unclosed $ignore_tags.
          if ($open_tag == '') {
            // Check whether this tag is contained in $ignore_tags.
            if (preg_match("`<($ignore_tags)(?:\s|>)`i", $chunks[$i], $matches)) {
              $open_tag = $matches[1];
            }
          }
          // Otherwise, check whether this is the closing tag for $open_tag.
          else {
            if (preg_match("`<\/$open_tag>`i", $chunks[$i], $matches)) {
              $open_tag = '';
            }
          }
          // Tag chunk is done, so next chunk must be text.
          $chunk_type = 'text';
        }
      }
    }
    $text = implode($chunks);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($this->settings['pirate_display_tip']) {
      return t('Avast! This website be taken over by pirates on September 19th. Yarr!');
    }
  }
}
