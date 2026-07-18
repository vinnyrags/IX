<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Hooks;

use Mythus\Hooks\BlockStyles;

/**
 * Registers the shared `core/heading` block styles that belong to every IX site.
 *
 * Currently just **Screen reader only** (`is-style-sr-only`) — visually hides a
 * heading while keeping it in the accessibility tree, for section labels that
 * would otherwise be visual noise. This is a platform-wide accessibility utility,
 * not a brand choice, so it lives here rather than being re-declared per child.
 * Child themes keep their own HeadingBlockStyles for brand-specific variants
 * (e.g. uppercase / bespoke type); `register_block_style` is additive, so the two
 * merge cleanly.
 *
 * The matching CSS ships in the Theme provider's `blocks/_wp-block-heading.scss`.
 */
class HeadingBlockStyles extends BlockStyles
{
    protected function styles(): array
    {
        return [
            'core/heading' => [
                ['name' => 'sr-only', 'label' => __('Screen reader only', 'ix')],
            ],
        ];
    }
}
