# Upgrading IX

Minor versions are additive and back-compatible unless noted here.

## 1.8.x → 1.9.0 — unopinionated cleanup (one opt-in required for some sites)

**Accordion icon is now opt-in.** `AccordionIconEnhancer` moved from
`IX\Providers\Theme\Hooks` to `IX\Providers\Theme\Features` and is no longer
registered by default. IX never shipped CSS for the chevron it injects, so a
site without its own accordion styles was getting a bare, unrotated SVG in place
of core's +/-; after upgrading those sites get core's +/- back. A site that
*does* style the chevron (vincentragosta.io) must opt in:

```php
use IX\Providers\Theme\Features\AccordionIconEnhancer;

protected array $features = [
    AccordionIconEnhancer::class,
];
```

**`wp-block-styles` is now the `CoreBlockStyles` feature.** Still on by default,
so nothing renders differently. A child that removed it with
`remove_theme_support('wp-block-styles')` can replace that with
`CoreBlockStyles::class => false` in its `$features` (the old removal still works).

**`SchemaBuilderService` author defaults to the site title** instead of a
hardcoded name. Pass `new SchemaBuilderService('Name')` if the Person/Organization
name should differ from Settings → General → Site Title.

**Preset reads have fallbacks.** No visual change — see "Preset token contract"
in the README for the slugs IX reads.

## 1.2.0 → 1.3.0 — focus-ring tokens (optional, back-compatible)

Nothing is required. The focus ring renders identically to v1.2.0 out of the box.

If a child theme previously re-declared the focus ring to recolour or resize it
— e.g. `:focus-visible { outline-color: … }` or a `:root :focus-visible { … }`
specificity bump to beat IX's bare selector — you can replace that with a token
set on `:root`, which is load-order-proof (IX declares no competing token):

```scss
// before
:root :focus-visible { outline: 3px solid var(--wp--preset--color--black); }

// after
:root {
  --ix-focus-ring-color: var(--wp--preset--color--black);
  --ix-focus-ring-width: 3px;   // omit to keep the 2px default
  // --ix-focus-ring-offset defaults to 2px
}
```

Any component-level `:focus-visible` (more specific) is unaffected and keeps
winning as before.
