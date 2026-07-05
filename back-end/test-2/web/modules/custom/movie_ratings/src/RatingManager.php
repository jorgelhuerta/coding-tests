<?php

namespace Drupal\movie_ratings;

use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Reads and writes star ratings for Movie nodes.
 */
class RatingManager {

  /**
   * Cache tag invalidated on every rating change.
   *
   * Used by anything that aggregates across all movies rather than a
   * single one: the Movies view's rating filter, the sidebar rankings.
   */
  const LIST_CACHE_TAG = 'movie_ratings_list';

  public function __construct(
    protected Connection $database,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected TimeInterface $time,
  ) {}

  /**
   * Records a rating, replacing any existing vote from the same IP.
   */
  public function submitRating(int $nid, int $rating, string $ipAddress): void {
    $this->database->merge('movie_ratings')
      ->keys(['nid' => $nid, 'ip_address' => $ipAddress])
      ->fields([
        'rating' => $rating,
        'created' => $this->time->getRequestTime(),
      ])
      ->execute();

    $this->cacheTagsInvalidator->invalidateTags([
      $this->getNodeCacheTag($nid),
      self::LIST_CACHE_TAG,
    ]);
  }

  /**
   * The cache tag for a single movie's aggregate rating display.
   */
  public function getNodeCacheTag(int $nid): string {
    return "movie_ratings:$nid";
  }

  /**
   * Returns the existing rating from this IP for a movie, if any.
   */
  public function getUserRating(int $nid, string $ipAddress): ?int {
    $value = $this->database->select('movie_ratings', 'mr')
      ->fields('mr', ['rating'])
      ->condition('nid', $nid)
      ->condition('ip_address', $ipAddress)
      ->execute()
      ->fetchField();

    return $value === FALSE ? NULL : (int) $value;
  }

  /**
   * Returns the average rating for a movie, or NULL if it has no votes.
   */
  public function getAverage(int $nid): ?float {
    $query = $this->database->select('movie_ratings', 'mr')
      ->condition('nid', $nid);
    $query->addExpression('AVG(rating)', 'average');
    $value = $query->execute()->fetchField();

    return $value === FALSE || $value === NULL ? NULL : (float) $value;
  }

  /**
   * Returns the number of votes cast for a movie.
   */
  public function getCount(int $nid): int {
    return (int) $this->database->select('movie_ratings', 'mr')
      ->condition('nid', $nid)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
