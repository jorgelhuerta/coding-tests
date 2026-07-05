<?php

namespace Drupal\movie_ratings\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters movies by minimum average star rating.
 *
 * Adds a HAVING-subquery condition against the movie_ratings table rather
 * than using Views' built-in aggregation (group by), so the rest of the
 * Movies view's fields and filters are unaffected.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("movie_minimum_average_rating")
 */
class MinimumAverageRating extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum average rating'),
      '#min' => 1,
      '#max' => 5,
      '#step' => 0.1,
      '#default_value' => $this->getScalarValue(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * Unwraps the exposed value so callers always get a plain scalar.
   *
   * Views stores exposed filter submissions as an array even for a single
   * scalar value.
   */
  protected function getScalarValue() {
    $value = $this->value;
    return is_array($value) ? reset($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $value = $this->getScalarValue();
    if ($value === '' || $value === NULL || $value === FALSE) {
      return;
    }

    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(
      $this->options['group'],
      "node_field_data.nid IN (SELECT nid FROM {movie_ratings} GROUP BY nid HAVING AVG(rating) >= $placeholder)",
      [$placeholder => (float) $value]
    );
  }

}
