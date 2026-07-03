# Movie Ratings

Star rating storage, Views integration and sidebar rankings for Movie nodes.
The submission form, flood control, and the average-rating display block are
added in later commits; this module currently provides the schema, the
service used to read/write ratings, the Views API integration, and the two
sidebar blocks built on top of it.

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

## Sidebar blocks (`movie_rankings` view)

Two blocks placed in the `sidebar` region of the default theme:

- **Top Rated Movies** — `movie_ratings` grouped by movie, `AVG(rating)`
  descending, top 5
- **Top Popular Movies** — same grouping, `COUNT(id)` (vote count)
  descending, top 5

Both use Views' native aggregation ("group by") query mode rather than
custom SQL. Built programmatically via `ViewExecutable::newDisplay()` and
`setOption()`/`setOverride()` — hand-writing a display's `defaults` override
map directly is unreliable, since it's schema-governed and gets reset to
"inherit everything" unless set through `setOverride()`.

## Setup

```
ddev drush en movie_ratings -y
```
