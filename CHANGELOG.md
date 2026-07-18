# Changelog

All notable changes to the IX parent theme are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions are
derived from annotated git tags (no `version` field in `composer.json`).
Entries predating this file (≤ v1.2.0) are tracked only as tags.

## [1.6.0] - 2026-07-18

### Added

- **Shared `Screen reader only` heading block style** (`core/heading` →
  `is-style-sr-only`) — visually hides a heading while keeping it in the
  accessibility tree, for section labels that would otherwise be visual noise.
  New `IX\Providers\Theme\Hooks\HeadingBlockStyles` (registered in the Theme
  provider's `$hooks`) + its frontend/editor CSS in
  `blocks/_wp-block-heading.scss` (the editor reveals it with a dashed outline +
  "👁 Screen reader only" label). Consolidated from CBA/AVFTB/MF, which each
  declared it locally; `register_block_style` is additive, so a child's own
  `HeadingBlockStyles` (brand variants like uppercase) still merges cleanly. The
  `is-style-sr-only` class name is unchanged, so existing content keeps working —
  no CMS migration. vincentragosta.io/ellenharvey (no prior sr-only) inherit it
  additively.

## [1.5.0] - 2026-07-17

### Added

- **`ContentPartial` — a default-on content-partial chrome Feature.** Lifts the
  header/footer partial system that CBA/AVFTB/MF had each copy-pasted into a base
  IX Feature (`IX\Providers\Theme\Features\ContentPartial\ContentPartial`),
  registered in `ThemeProvider::$features`. It provides the `content-partial` CPT +
  `partial-type` taxonomy (seeded header/footer terms, one `is_default` per type),
  the canonical ACF groups (loaded from the Feature's own `acf-json/` —
  `group_content_partial_fields` + `group_partial_overrides`, keys `field_content_partial_*`
  / `field_partial_overrides_*`), a `PartialResolver` (page-level default/custom/disabled
  cascade), the `ContentPartialPost` model, and injects the resolved partials into the
  Timber context as `header_partial` / `footer_partial`.
  - **The theme still owns the header/footer *markup*** — `views/header.twig` /
    `views/footer.twig` render the injected data (guarded by `{% if *_partial %}`), so
    a theme that builds its chrome directly in those templates simply ignores the
    context vars. IX only supplies the data.
  - **Default-on, opt-out via `ContentPartial::class => false`** in a child
    ThemeProvider's `$features` — for sites (e.g. vincentragosta.io, ellenharvey) that
    build header/footer purely in-theme with no CPT.
  - **Canonical keys, cross-site.** A site that stored partial fields under a prefixed
    key scheme (MF's `field_mbf_*`) rekeys its ACF pointers to the canonical keys
    (values, keyed by name, survive) — same pattern as the Settings Hub.

## [1.3.0] - 2026-07-16

### Changed

- **`_focus.scss` is now tunable via tokens, without changing the default.** The
  keyboard-focus ring keeps its exact current appearance (`2px solid currentColor`,
  `2px` offset) but reads three optional custom properties:
  `--ix-focus-ring-color`, `--ix-focus-ring-width`, `--ix-focus-ring-offset`.
  Defaults are supplied as `var(…, fallback)` — IX declares **no** token values, so
  a child's `:root { --ix-focus-* }` is uncontested and **load-order-proof**.
  - IX stays unopinionated: it ships structure + neutral defaults, no brand colour.
  - **Back-compatible:** sites that set no token render byte-identically to v1.2.0.
  - **Retires the cascade hacks:** a child that re-declared `:focus-visible` (or used
    a `:root :focus-visible` specificity bump) to recolour/resize the ring can now
    set the token on `:root` instead. See `UPGRADING.md`.

[1.3.0]: https://github.com/vinnyrags/ix/compare/v1.2.0...v1.3.0
