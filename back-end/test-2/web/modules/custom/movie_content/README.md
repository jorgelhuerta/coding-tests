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

A table view of all Movie nodes with exposed filters:

- **Category** — `taxonomy_index_tid` filter on `field_category`, multi-select
- **Director** — via a relationship to the referenced Director node, filtering
  its title (`contains`), since entity-reference-to-node fields don't get a
  dedicated name-based filter plugin the way taxonomy references do
- **Actor** — same pattern, via a relationship to the referenced Actor node

A star-rating filter is added once the `movie_ratings` module exposes the
ratings table to Views.

## Setup

```
ddev drush en movie_content -y
```
