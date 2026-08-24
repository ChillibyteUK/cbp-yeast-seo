# CBP Yeast SEO

A small, theme-agnostic WordPress plugin for hand-authored JSON-LD schema
markup, built to sit alongside Yoast SEO rather than fight it. Ported from
`cb-hts2026`'s ACF-based `inc/cb-schema.php`, with ACF replaced by native
post meta.

Starts with schema only — the plan is for this to grow into a fuller Yoast
replacement over time, one feature at a time.

## What it does

- Adds a **Schema (JSON-LD)** field to every public post type (minus
  attachments), as a classic meta box in the block editor's **Meta Boxes**
  drawer — deliberately not a Document sidebar panel, so it doesn't compete
  for space with Yoast's own panel.
- Accepts a single JSON object, a top-level array of objects, or a
  `@graph`-wrapped document. Leave it blank to fall back to Yoast's defaults
  entirely.
- Outputs the saved schema as a `<script type="application/ld+json">` tag in
  `wp_head`, after Yoast's own graph.
- If the saved schema declares an `Organization` node, Yoast's own
  Organization node is suppressed on that page (via the
  `wpseo_schema_needs_organization` filter) so there's no duplicate —
  everywhere else, Yoast's node is left alone as the target for its own
  `publisher`/`about` references.
- Validates the saved value after every save (valid JSON, has `@context`
  and `@type`, an `Organization` node's `@id` ends in `#organization` so
  Yoast's cross-references resolve, no accidental duplication of types
  Yoast already emits) and shows a **non-blocking** admin notice if
  something looks off. Unlike the ACF source, this never blocks the save —
  there's no `acf/validate_value`-equivalent hook for post meta edited
  through the block editor's REST-backed meta box API, so validation here
  is advisory only.

## Settings

**Settings → CBP Yeast SEO** has one toggle for now: enable/disable the
schema module entirely. Turning it off stops schema output and hides the
meta box, but never touches already-saved field content.

## Deliberately not included

- **FAQPage schema** — each theme handles its own FAQ block/accumulator
  differently, and not every theme installing this plugin will have one.
  Left in the theme, not centralised here.
- **Breadcrumb microdata** — out of scope for now.

## Requirements

- WordPress with the block editor (native post meta + `add_meta_box()`).
- Yoast SEO active, for the Organization-suppression filter to have any
  effect — the plugin still registers the field and outputs schema without
  it, the filter hook is just a no-op if Yoast isn't there to call it.
