<?php

namespace Drupal\movie_ratings\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\movie_ratings\RatingManager;
use Drupal\node\NodeInterface;
use Drupal\movie_ratings\Form\MovieRatingForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows a movie's average rating and the submission form.
 */
#[Block(
  id: 'movie_rating_block',
  admin_label: new TranslatableMarkup('Movie rating'),
)]
class MovieRatingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RatingManager $ratingManager,
    protected FormBuilderInterface $formBuilder,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('movie_ratings.manager'),
      $container->get('form_builder'),
      $container->get('current_route_match'),
    );
  }

  /**
   * Returns the movie node for the current route, if any.
   */
  protected function getMovieNode(): ?NodeInterface {
    $node = $this->routeMatch->getParameter('node');
    return $node instanceof NodeInterface && $node->bundle() === 'movie' ? $node : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIf($this->getMovieNode() !== NULL)
      ->addCacheContexts(['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getMovieNode();
    if (!$node) {
      return [];
    }

    $nid = (int) $node->id();
    $average = $this->ratingManager->getAverage($nid);
    $count = $this->ratingManager->getCount($nid);
    $rounded = $average ? (int) round($average) : 0;

    $stars = [];
    for ($i = 1; $i <= 5; $i++) {
      $stars[$i] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => ['class' => ['movie-rating-star', $i <= $rounded ? 'is-filled' : 'is-empty']],
        '#value' => '★',
      ];
    }

    $summary_text = $average
      ? $this->formatPlural($count, '@avg / 5 (1 vote)', '@avg / 5 (@count votes)', ['@avg' => number_format($average, 1)])
      : $this->t('No ratings yet');

    return [
      'summary' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['movie-rating-summary']],
        'stars' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['movie-rating-stars'], 'aria-hidden' => 'true'],
          'items' => $stars,
        ],
        'text' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => ['class' => ['movie-rating-text']],
          '#value' => $summary_text,
        ],
      ],
      'form' => $this->formBuilder->getForm(MovieRatingForm::class, $node),
      '#attached' => ['library' => ['movie_ratings/rating']],
      // Whether this visitor already has a vote for this movie is
      // visitor-specific and there's no per-IP cache context in core, so
      // this block is intentionally left uncached.
      '#cache' => ['max-age' => 0],
    ];
  }

}
