# Movie Ratings

Star rating storage and Views integration for Movie nodes. The submission
form, flood control, and the display block are added in later commits; this
module currently provides the schema, the service used to read/write
ratings, and the Views API integration those pieces (and the sidebar
blocks/Movies view rating filter) build on.

## Schema

`movie_ratings` table (see `movie_ratings.install`): `id`, `nid`, `rating`
(1-5), `ip_address`, `created`. A unique key on `(nid, ip_address)` means a
second submission from the same IP for the same movie **replaces** the
previous vote rather than adding a duplicate — one vote per visitor per
movie.

## RatingManager service (`movie_ratings.manager`)

- `submitRating($nid, $rating, $ipAddress)` — upsert via `Connection::merge()`
- `getUserRating($nid, $ipAddress)` — the existing vote from this IP, if any
- `getAverage($nid)` / `getCount($nid)` — aggregate helpers used by the
  display block

## Views integration (`hook_views_data()`)

Exposes `movie_ratings` as a Views base table (with a relationship to the
rated node) and adds a reverse relationship on `node_field_data` so
node-based views — the Movies listing, the sidebar blocks — can relate to
a movie's ratings and aggregate over them (`AVG(rating)`, `COUNT(id)`) using
Views' built-in "Use aggregation" (group by) query mode.

## Setup

```
ddev drush en movie_ratings -y
```
