# Upgrading IX

Minor versions are additive and back-compatible unless noted here.

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
