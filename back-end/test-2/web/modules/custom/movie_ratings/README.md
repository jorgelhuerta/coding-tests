# Movie Ratings

Star rating storage, submission, display, Views integration and sidebar
rankings for Movie nodes.

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
- `getNodeCacheTag($nid)` — cache tag invalidated whenever that movie's
  rating changes; also invalidates the shared `movie_ratings_list` tag for
  anything that aggregates across movies (the rankings blocks, the Movies
  view's rating filter) — see the `hook_views_pre_render()` note below

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

## Display block (`MovieRatingBlock`)

Context-aware block (reads the movie node from the current route, not a
context mapping — simpler for a block that's only ever meaningful on a node
page) showing the average as five filled/empty stars, the vote count, and
the submission form together, per requirement #7. Placed in the
`content_above` region site-wide; `blockAccess()` hides it anywhere that
isn't a Movie node page, so no visibility condition/region restriction is
needed.

Deliberately uncached (`#cache => ['max-age' => 0]`): whether *this* visitor
already has a vote is personalized (`RatingManager::getUserRating()` by IP),
and Drupal core has no per-IP cache context (by design — it would blow up
reverse-proxy cache cardinality), so there's no safe cache key to vary on
short of disabling caching for this block.

The star widget (`movie_ratings/rating` library) replaces the native
`select` with clickable star buttons via vanilla JS, keeping the select
itself as the real submitted value (hidden, not removed) so the form still
works with JS disabled.

## Views integration (`hook_views_data()` / `hook_views_pre_render()`)

Exposes `movie_ratings` as a Views base table (with a relationship to the
rated node) and adds a reverse relationship on `node_field_data` so
node-based views — the Movies listing, the sidebar blocks — can relate to
a movie's ratings and aggregate over them (`AVG(rating)`, `COUNT(id)`) using
Views' built-in "Use aggregation" (group by) query mode.

Since `movie_ratings` isn't a tracked entity type, Views has no way to know
when it changes; `hook_views_pre_render()` tags the `movies` and
`movie_rankings` views with `RatingManager::LIST_CACHE_TAG`, which
`submitRating()` invalidates on every vote.

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
