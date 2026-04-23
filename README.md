# IX

A foundational WordPress parent theme built on [Mythus](https://github.com/vinnyrags/mythus) with Timber 2.x / Twig support. IX is the **bridge layer** between Mythus's theme-agnostic framework and template rendering — it extends `Mythus\Provider` with template directory resolution, Twig filter registration, and theme-aware path overrides.

Site-specific themes (like [vincentragosta.io](https://github.com/vinnyrags/vincentragosta.io)) extend IX as a parent theme, inheriting base providers and adding their own.

Named after one of the Aeons in Honkai: Star Rail.

## Stack

- PHP 8.4+ with strict types
- WordPress 6.0+
- [Mythus](https://github.com/vinnyrags/mythus) framework (for the provider pattern, DI, support managers)
- [Timber 2.x](https://timber.github.io/docs/v2/) / Twig for templating
- [svg-sanitize](https://github.com/darylldoyle/svg-sanitize) for safe SVG uploads
- esbuild + Sass for asset compilation
- PHPUnit 9 + WorDBless for PHP tests
- Vitest + Testing Library (jsdom) for JS tests

## Install

IX is a WordPress theme distributed via Composer:

```json
{
  "require": {
    "vincentragosta/ix": "dev-main"
  },
  "extra": {
    "installer-paths": {
      "wp-content/themes/{$name}/": ["type:wordpress-theme"]
    }
  }
}
```

Mythus must already be installed at `wp-content/mu-plugins/mythus/` before IX is bootstrapped.

## What's Inside

```
src/
├── Theme.php                         # Theme entry point — extends Timber\Site, bootstraps providers
├── Providers/
│   ├── Provider.php                  # Bridge class — extends Mythus\Provider, adds Timber support
│   ├── Theme/                        # Core theme setup, supports, global assets
│   │   ├── ThemeProvider.php
│   │   ├── Features/                 # EnableSvgUploads, DisableComments, DisablePosts, …
│   │   ├── Hooks/                    # ButtonIconEnhancer, CoverBlockStyles, SocialIconChoices, …
│   │   ├── blocks/                   # Generic blocks (testimonials, accordion, cover variants)
│   │   ├── patterns/                 # Fallback block patterns
│   │   ├── templates/                # Shared Twig partials
│   │   └── assets/                   # SCSS, JS, SVG icons
│   ├── PostType/                     # Base custom post type provider
│   ├── Project/                      # Projects CPT infrastructure
│   └── Blog/                         # Blog infrastructure (BlogPost, BlogRepository, BlogProvider)
├── Models/
│   ├── Post.php                      # Base Timber\Post extension with ACF-aware helpers
│   └── Image.php                     # Image model — lazy loading, responsive sizes, resize API
├── Repositories/
│   ├── Repository.php                # Base repository over Timber/WP_Query
│   └── RepositoryInterface.php
└── Services/
    ├── IconService.php               # SVG icon rendering (newable via IconServiceFactory)
    ├── IconServiceFactory.php
    ├── SchemaBuilderService.php      # JSON-LD structured data (BlogPosting, CreativeWork, BreadcrumbList)
    └── SvgSanitizerService.php
```

## Build System

The canonical build script is `scripts/build-providers.js`. It auto-discovers every provider with assets or blocks and compiles:

- Provider SCSS: `src/Providers/{Name}/assets/scss/index.scss` → `dist/css/{slug}.css`
- Provider JS: `src/Providers/{Name}/assets/js/*.js` → `dist/js/{slug}/*.js`
- Block editor JS: `blocks/{name}/editor/index.js` → `dist/js/{name}.js`
- Block frontend style: `blocks/{name}/style.scss` → `dist/css/{name}.css`
- Block editor style: `blocks/{name}/editor/editor.scss` → `dist/css/{name}-editor.css`

Child themes invoke the same script via `node ../ix/scripts/build-providers.js` — the script uses `process.cwd()` as the theme root, so it works for any theme that runs it.

Themes can provide a `scripts/build-providers.config.js` exporting `sassLoadPaths` to share SCSS partials (e.g., common breakpoints).

## Testing

```bash
composer install
npm install
composer test     # PHP unit + integration tests
npm run test:js   # JavaScript unit tests via Vitest
```

Tests mirror the source tree. See `tests/Unit/` and `tests/Integration/`.

## What IX Provides vs. What It Doesn't

**IX provides:**
- Timber/Twig bridge over Mythus
- Base `Provider` with template resolution
- Core `ThemeProvider` (theme supports, asset loading, global Twig functions)
- `PostTypeProvider` for registering CPTs from JSON config
- `BlogProvider` (posts, pagination, BlogPost model, BlogRepository)
- `ProjectProvider` (projects CPT, project pages)
- Shared models (`Post`, `Image`), repositories, and services
- Generic features and hooks that apply to any IX-based theme

**IX does not provide:**
- Any site-specific branding or theming — that's the child theme's job
- Blocks or patterns specific to a site (IX has generic ones only)
- E-commerce, Discord bot, or third-party integrations

## Context

IX is the parent theme for [vincentragosta.io](https://github.com/vinnyrags/vincentragosta.io), the personal website of Vincent Ragosta. It was extracted into this standalone Composer package so child themes across projects can share the same foundation.
