<?php

namespace Drupal\movie_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Endroid\QrCode\Builder\Builder;

/**
 * Renders a Link field as a QR code pointing at the URL.
 *
 * @FieldFormatter(
 *   id = "youtube_trailer_qr",
 *   label = @Translation("QR code"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class YoutubeTrailerQrFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (empty($item->uri)) {
        continue;
      }

      $url = \Drupal\Core\Url::fromUri($item->uri)->toString();
      $result = (new Builder())->build(data: $url, size: 200, margin: 10);

      $elements[$delta] = [
        '#theme' => 'movie_trailer_qr_code',
        '#data_uri' => $result->getDataUri(),
        '#url' => $url,
        '#cache' => ['contexts' => ['url']],
      ];
    }

    return $elements;
  }

}
