# Changelog

All notable changes to the IX parent theme are documented here. The format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions are
derived from annotated git tags (no `version` field in `composer.json`).
Entries predating this file (≤ v1.2.0) are tracked only as tags.

## [1.7.0] - 2026-08-24

### Security

- **`DisableAuthorArchives` — a default-on Feature that stops WordPress
  publishing valid login names to anonymous visitors.** New
  `IX\Providers\Theme\Features\DisableAuthorArchives`, registered in the Theme
  provider's `$features`, so consumers inherit it without opting in.

  WordPress leaks usernames through four separate routes, and closing only the
  obvious one leaves the account list readable:

  1. `/author/<login>/` — the archive itself, which Google indexes
  2. `/?author=<id>` — 301s to the archive, leaking the login in `Location`
  3. `/wp-sitemap-users-1.xml` — core feeds those archive URLs to search engines
  4. `/wp-json/wp/v2/users` — returns every user's slug as JSON

  All four were open on all four ARTHOUSE sites. This is not theoretical: on
  2026-08-11 a site was compromised by an attacker logging in as a known
  administrator with a valid password. They did not have to guess the account —
  `/author/<login>/` was indexed and `/?author=1` handed the name over on
  request. Username enumeration plus an unthrottled login form is most of that
  attack, and rate limiting alone does not fix it, because the attacker still
  knows precisely which account to target.

  The guard hooks `template_redirect` at **priority 0** and unhooks
  `redirect_canonical`. That ordering is load-bearing rather than incidental:
  core registers `redirect_canonical()` at priority 10, and it is what converts
  `/?author=1` into the leaking 301 — so setting a 404 at any later priority
  still gives the username away. `DisableAuthorArchivesTest` asserts the
  priority for exactly that reason.

  Viewers who can already `list_users` keep normal behaviour, so the block
  editor's author controls are unaffected. A site that genuinely wants public
  author archives opts out in its child provider:
  `DisableAuthorArchives::class => false`.

  Shucked is **not** covered — it runs no Mythus/IX — and needs the standalone
  deploy-kit mu-plugin instead.

## [1.6.2] - 2026-08-16

### Security

- **Bumped `twig/twig` 3.24.0 → 3.28.0.** 3.24.0 carried 17 advisories, the
  substantive ones being Twig sandbox filter/tag/function allow-list bypasses
  (CVE-2026-49981, CVE-2026-48808 and related), fixed upstream in 3.27.0. Twig
  arrives transitively via `timber/timber: 2.x-dev`.

  Practical risk for IX consumers was low — Timber renders developer-authored
  templates, not untrusted input, and the sandbox is not enabled for theme
  rendering — so this is hygiene rather than an exploitable hole. It is shipped
  separately from any feature work so it can be adopted on its own.

  **Note for consumers:** every site carries *three* vendor copies of Twig —
  root, `themes/ix`, and the child theme — and they drift independently. All five
  consuming sites were bumped directly on 2026-08-16; this release closes the
  parent-theme copy at source. Adopting it via
  `composer update vincentragosta/ix` **wipes `ix/node_modules`**, so run
  `npm install` inside the ix copy afterwards to restore build and test tooling.

## [1.6.1] - 2026-08-01

### Fixed

- **Polyfilled `localStorage` for Node 22+ in the test setup.** Under Node's
  built-in Web Storage, jsdom's `localStorage` isn't wired onto the global in the
  vitest environment, leaving it undefined — which broke the `afterEach` cleanup
  and any code under test that reads or writes it. `scripts/test-setup.js` now
  installs a minimal Map-backed shim when the environment doesn't provide one;
  inert wherever jsdom already supplies it.
- De-staled `content-slider/view.test.js` for the `SPLIDE_CONFIG` →
  `SPLIDE_BASE_CONFIG` rename.

*(Logged retroactively 2026-08-16 — this version was tagged but never added to the
changelog, which left a gap between 1.6.0 and 1.6.2.)*

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
