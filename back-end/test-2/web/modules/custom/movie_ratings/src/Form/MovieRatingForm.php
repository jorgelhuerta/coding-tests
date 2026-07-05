<?php

namespace Drupal\movie_ratings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\node\NodeInterface;
use Drupal\movie_ratings\RatingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Star rating submission form for a single Movie node.
 */
class MovieRatingForm extends FormBase {

  const FLOOD_EVENT = 'movie_ratings.submit';
  const FLOOD_LIMIT = 5;
  const FLOOD_WINDOW = 3600;

  public function __construct(
    protected RatingManager $ratingManager,
    protected FloodInterface $flood,
    protected RequestStack $currentRequestStack,
  ) {}

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('movie_ratings.manager'),
      $container->get('flood'),
      $container->get('request_stack'),
    );
  }

  public function getFormId() {
    return 'movie_rating_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    $form['#node_id'] = $node?->id();
    $form['#attributes']['class'][] = 'movie-rating-form';

    $ip = $this->currentRequestStack->getCurrentRequest()->getClientIp();
    $existing = $node ? $this->ratingManager->getUserRating((int) $node->id(), $ip) : NULL;

    $form['rating'] = [
      '#type' => 'select',
      '#title' => $this->t('Your rating'),
      '#options' => [
        1 => $this->t('1 star'),
        2 => $this->t('2 stars'),
        3 => $this->t('3 stars'),
        4 => $this->t('4 stars'),
        5 => $this->t('5 stars'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $existing,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $existing ? $this->t('Update rating') : $this->t('Submit rating'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form['#node_id']) {
      $form_state->setErrorByName('rating', $this->t('This form is not attached to a movie.'));
      return;
    }

    $ip = $this->currentRequestStack->getCurrentRequest()->getClientIp();
    if (!$this->flood->isAllowed(self::FLOOD_EVENT, self::FLOOD_LIMIT, self::FLOOD_WINDOW, $ip)) {
      $form_state->setErrorByName('rating', $this->t('Too many rating submissions. Please try again later.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = $this->currentRequestStack->getCurrentRequest()->getClientIp();
    $this->flood->register(self::FLOOD_EVENT, self::FLOOD_WINDOW, $ip);

    $this->ratingManager->submitRating(
      (int) $form['#node_id'],
      (int) $form_state->getValue('rating'),
      $ip
    );

    $this->messenger()->addStatus($this->t('Thanks for your rating!'));
  }

}
