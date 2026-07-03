<?php

namespace Drupal\movie_ratings;

use Drupal\Core\Database\Connection;

/**
 * Reads and writes star ratings for Movie nodes.
 */
class RatingManager {

  public function __construct(protected Connection $database) {}

  /**
   * Records a rating, replacing any existing vote from the same IP.
   */
  public function submitRating(int $nid, int $rating, string $ipAddress): void {
    $this->database->merge('movie_ratings')
      ->keys(['nid' => $nid, 'ip_address' => $ipAddress])
      ->fields([
        'rating' => $rating,
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
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
