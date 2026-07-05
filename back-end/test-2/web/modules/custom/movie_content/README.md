# Movie Content

Defines the content architecture for the movie rating system: three content
types and the fields that relate them.

## Content types

- **Movie** (`movie`): `Title` (base field) plus:
  - `field_director` — entity reference to `Director` nodes, unlimited cardinality
  - `field_actor` — entity reference to `Actor` nodes, unlimited cardinality
  - `field_category` — entity reference to the `categories` taxonomy vocabulary, unlimited cardinality
  - `field_year` — integer, "Year of Release", required, minimum 1888 (the year of the first known film)
- **Director** (`director`): `Title` (base field) + `field_bio` (long text)
- **Actor** (`actor`): `Title` (base field) + `field_bio` (long text, shared field storage with Director)
- `field_youtube_trailer` on Movie — a Link field, external URLs only, no
  link-text override (see the QR code section below)

## Taxonomy

`Categories` vocabulary (`categories`), seeded with: Action, Drama, Comedy,
Sci-Fi, Thriller, Horror, Romance, Documentary.

## Approach

Content types, fields and the vocabulary were created once against a live
site via the Entity API (`NodeType`, `FieldStorageConfig`, `FieldConfig`,
`Vocabulary`) and the resulting configuration was captured into
`config/install/`. Drupal installs these YAML files automatically the first
time the module is enabled, so a fresh site gets the exact same architecture
with no manual clicking through the Field UI required.

Taxonomy terms are content, not configuration, so they can't ship in
`config/install`; they're seeded instead via `hook_install()` in
`movie_content.module`.

## Movies view (`/movies`)

The `movies` view (category/actor/director/rating filters) ships from the
`movie_ratings` module, not this one — its rating filter depends on a plugin
that lives there, and `movie_ratings` already depends on `movie_content`, so
that's the direction the dependency has to run. See that module's README.

## QR code trailer (bonus)

`field_youtube_trailer` is displayed on the Movie full page via a custom
field formatter (`YoutubeTrailerQrFormatter`, plugin ID
`youtube_trailer_qr`) rather than the default Link formatter, rendering a
QR code image + the required caption ("Scan the QR code to watch the
trailer of this movie.") instead of a plain link.

The QR code is generated **server-side** as a PNG data URI using
[endroid/qr-code](https://github.com/endroid/qr-code) (added as a composer
dependency — `ddev composer require endroid/qr-code`), rather than a
client-side JS library or a third-party image API. That avoids a runtime
network dependency for reviewers (no external API call to render the page)
and avoids the correctness risk of hand-rolling a QR encoder (Reed-Solomon
error correction, matrix placement) from scratch.

## Setup

```
ddev drush en movie_content -y
```
