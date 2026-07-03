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

The `Categories` vocabulary itself is added in a later commit, alongside its
seeded terms. `field_category` references it by machine name and simply has
no options until the vocabulary exists — Drupal doesn't require the target
bundle to exist at field-creation time.

## Approach

All content types and fields were created once against a live site via the
Entity API (`NodeType`, `FieldStorageConfig`, `FieldConfig`) and the resulting
configuration was captured into `config/install/`. Drupal installs these
YAML files automatically the first time the module is enabled, so a fresh
site gets the exact same architecture with no manual clicking through the
Field UI required.

## Setup

```
ddev drush en movie_content -y
```
