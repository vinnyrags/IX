# Changelog

All notable changes to the IX parent theme are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions are
derived from annotated git tags (no `version` field in `composer.json`).
Entries predating this file (≤ v1.2.0) are tracked only as tags.

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
