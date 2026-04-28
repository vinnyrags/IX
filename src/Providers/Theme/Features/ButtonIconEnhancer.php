<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features;

use IX\Services\IconServiceFactory;
use Mythus\Contracts\Feature;
use DOMDocument;
use DOMXPath;

/**
 * Adds optional icon support to the core/button block.
 *
 * Owns the full surface: the editor-side picker (block attributes +
 * inspector controls + icon-catalog localization) and the frontend
 * render filter that injects the SVG into rendered button HTML. As
 * a Feature, the entire surface is opt-in per consumer — IX no
 * longer enables it by default.
 */
class ButtonIconEnhancer implements Feature
{
    private const SCRIPT_HANDLE = 'ix-button-icon-js';
    private const SCRIPT_PATH = 'js/theme/button.js';

    /**
     * Create the enhancer with its icon factory dependency.
     *
     * @param IconServiceFactory $iconFactory Factory for resolving icon SVG content.
     */
    public function __construct(
        private readonly IconServiceFactory $iconFactory,
    ) {}

    public function register(): void
    {
        // Frontend: inject SVG into rendered button block output.
        add_filter('render_block_core/button', [$this, 'render'], 10, 2);

        // Editor: register the icon-picker UI and its data.
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);
        add_action('enqueue_block_editor_assets', [$this, 'localizeIconData'], 99);
    }

    /**
     * Filter the button block output to add icons.
     *
     * @param string $content Rendered block HTML.
     * @param array{blockName: string, attrs: array<string, mixed>} $block Parsed block data.
     */
    public function render(string $content, array $block): string
    {
        if (!$this->shouldEnhance($block)) {
            return $content;
        }

        $iconName = $block['attrs']['selectedIcon'];
        $icon = $this->iconFactory->create($iconName);
        if (!$icon->exists()) {
            return $content;
        }

        $position = $block['attrs']['iconPosition'] ?? 'right';

        return $this->enhanceButton($content, (string) $icon, $position, $iconName);
    }

    /**
     * Enqueue the editor-side icon picker script.
     *
     * Logic mirrors ThemeProvider::enqueueParentEditorScript() but is
     * inlined here so the Feature owns its full surface without coupling
     * back to the parent provider.
     */
    public function enqueueEditorAssets(): void
    {
        $fullPath = get_template_directory() . '/dist/' . self::SCRIPT_PATH;

        if (!file_exists($fullPath)) {
            return;
        }

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            get_template_directory_uri() . '/dist/' . self::SCRIPT_PATH,
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data'],
            filemtime($fullPath),
            true
        );
    }

    /**
     * Localize icon catalog data for the editor picker.
     *
     * Uses wp_add_inline_script with wp_json_encode for reliable data
     * serialization. Skips when the picker script isn't loaded.
     */
    public function localizeIconData(): void
    {
        if (!wp_script_is(self::SCRIPT_HANDLE, 'registered') && !wp_script_is(self::SCRIPT_HANDLE, 'enqueued')) {
            return;
        }

        $data = [
            'iconOptions' => $this->iconFactory->options('icon', __('— No Icon —', 'ix')),
            'iconContentMap' => $this->iconFactory->contentMap('icon'),
        ];

        wp_add_inline_script(
            self::SCRIPT_HANDLE,
            'var parentThemeButtonIconData = ' . wp_json_encode($data) . ';',
            'before'
        );
    }

    /**
     * Check if this button block has a selected icon attribute.
     *
     * @param array{blockName?: string, attrs?: array<string, mixed>} $block Parsed block data.
     */
    private function shouldEnhance(array $block): bool
    {
        return isset($block['blockName'])
            && $block['blockName'] === 'core/button'
            && !empty($block['attrs']['selectedIcon']);
    }

    /**
     * Enhance the button HTML by injecting an icon span via DOMDocument.
     *
     * @param string $content Original button block HTML.
     * @param string $svg Rendered SVG markup for the icon.
     * @param string $position Icon position relative to the label ('left' or 'right').
     * @param string $iconName The selected icon name for CSS targeting.
     */
    private function enhanceButton(string $content, string $svg, string $position, string $iconName): string
    {
        $dom = $this->createDom($content);
        if (!$dom) {
            return $content;
        }

        $xpath = new DOMXPath($dom);

        $this->addWrapperClasses($xpath, $position);
        $this->insertIcon($dom, $xpath, $svg, $position, $iconName);

        return $this->getInnerHtml($dom);
    }

    /**
     * Create a DOMDocument from HTML content wrapped in a root element.
     *
     * @param string $content Raw HTML to parse.
     * @return DOMDocument|null The parsed document, or null on parse failure.
     */
    private function createDom(string $content): ?DOMDocument
    {
        $dom = new DOMDocument();

        $wrapped = '<div id="__wrapper__">' . $content . '</div>';
        if (!@$dom->loadHTML(
            '<?xml encoding="UTF-8">' . $wrapped,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        )) {
            return null;
        }

        return $dom;
    }

    /**
     * Add icon-related CSS classes to the .wp-block-button wrapper.
     *
     * @param DOMXPath $xpath XPath instance bound to the button document.
     * @param string $position Icon position ('left' or 'right') for the directional class.
     */
    private function addWrapperClasses(DOMXPath $xpath, string $position): void
    {
        $wrappers = $xpath->query("//*[contains(@class, 'wp-block-button')]");

        foreach ($wrappers as $wrapper) {
            $currentClass = $wrapper->getAttribute('class');
            $newClasses = $currentClass . ' has-icon icon-pos-' . esc_attr($position);
            $wrapper->setAttribute('class', $newClasses);
        }
    }

    /**
     * Insert an icon span into the .wp-block-button__link element.
     *
     * @param DOMDocument $dom The button document for creating new nodes.
     * @param DOMXPath $xpath XPath instance for locating the link element.
     * @param string $svg Rendered SVG markup to embed inside the icon span.
     * @param string $position 'left' prepends, 'right' appends the icon span.
     * @param string $iconName The selected icon name for CSS targeting.
     */
    private function insertIcon(DOMDocument $dom, DOMXPath $xpath, string $svg, string $position, string $iconName): void
    {
        $links = $xpath->query("//*[contains(@class, 'wp-block-button__link')]");

        foreach ($links as $link) {
            $iconSpan = $dom->createElement('span');
            $iconSpan->setAttribute('class', 'wp-block-button__icon ' . esc_attr($iconName));
            $iconSpan->setAttribute('aria-hidden', 'true');

            $svgNode = $this->createSvgFragment($dom, $svg);
            if ($svgNode) {
                $iconSpan->appendChild($svgNode);
            }

            if ($position === 'right') {
                $link->appendChild($iconSpan);
            } else {
                $link->insertBefore($iconSpan, $link->firstChild);
            }
        }
    }

    /**
     * Parse SVG markup and import it as a node into the target document.
     *
     * @param DOMDocument $dom Target document to import the SVG node into.
     * @param string $svg Raw SVG markup string.
     * @return \DOMNode|null The imported SVG node, or null on parse failure.
     */
    private function createSvgFragment(DOMDocument $dom, string $svg): ?\DOMNode
    {
        $tempDom = new DOMDocument();
        if (!@$tempDom->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $svg . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        )) {
            return null;
        }

        $svgElement = $tempDom->getElementsByTagName('svg')->item(0);
        if (!$svgElement) {
            return null;
        }

        return $dom->importNode($svgElement, true);
    }

    /**
     * Extract the inner HTML from the __wrapper__ div used during parsing.
     *
     * @param DOMDocument $dom The document containing the wrapper element.
     */
    private function getInnerHtml(DOMDocument $dom): string
    {
        $wrapper = $dom->getElementById('__wrapper__');
        if (!$wrapper) {
            return '';
        }

        $html = '';
        foreach ($wrapper->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return $html;
    }
}
