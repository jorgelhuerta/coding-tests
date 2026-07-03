# Movie Ratings

Star rating storage, submission, Views integration and sidebar rankings for
Movie nodes. The average-rating display block is added in a later commit;
this module currently provides the schema, the read/write service, the
submission form with flood control, the Views API integration, and the two
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

## Submission form (`MovieRatingForm`)

A 1-5 star `select` bound to a specific movie node (passed as a form
argument, not a route parameter, since it's embedded in a block rather than
served at its own path). Pre-fills the current visitor's existing vote, if
any, and re-labels the submit button to "Update rating" in that case.

Flood control (`\Drupal::flood()`, or rather the injected `flood` service):
5 submissions per IP per hour (`movie_ratings.submit` event). Checked in
`validateForm()` so a blocked visitor sees a form error instead of a
silently-dropped vote; registered in `submitForm()` only after a rating
actually gets recorded.

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

## Movies view (`/movies`)

This module also ships the `movies` view (a table of all Movie nodes) even
though it's conceptually a `movie_content` concern, because its star-rating
filter depends on a plugin that has to live here:

- **Category** — `taxonomy_index_tid` filter on `field_category`, multi-select
- **Director** / **Actor** — via a relationship to the referenced node,
  filtering its title (`contains`), since entity-reference-to-node fields
  don't get a dedicated name-based filter the way taxonomy references do
- **Minimum average rating** — a custom filter plugin
  (`MinimumAverageRating`, plugin ID `movie_minimum_average_rating`) that
  adds a `nid IN (SELECT ... HAVING AVG(rating) >= ?)` subquery, rather than
  turning the whole view into a grouped query via Views' built-in
  aggregation — that would have required marking every existing field's
  `group_type`, for one added filter.

Watch for: exposed filter values arrive in `$this->value` as an array (e.g.
`[4]`) even for a single scalar textfield, not as a plain scalar — casting
it directly (`(float) $this->value`) silently produces `1.0` for any
non-empty array. Unwrap with `reset()` first.

## Setup

```
ddev drush en movie_ratings -y
```
