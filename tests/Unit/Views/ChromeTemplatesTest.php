<?php

namespace IX\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * The three states of IX's header.twig / footer.twig: partial, fallback, disabled.
 */
class ChromeTemplatesTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twig = new Environment(new FilesystemLoader(dirname(__DIR__, 3) . '/views'));
        $this->twig->addFunction(new TwigFunction('function', fn () => '<ul class="menu"></ul>', ['is_safe' => ['html']]));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function chromeProvider(): array
    {
        return [
            'header' => ['header', 'site-header'],
            'footer' => ['footer', 'site-footer'],
        ];
    }

    /**
     * @dataProvider chromeProvider
     */
    public function testRendersPartialInsideLandmark(string $type, string $class): void
    {
        $html = $this->render($type, [
            "{$type}_partial" => ['content' => '<p class="from-cms">CMS</p>'],
        ]);

        $this->assertStringContainsString("<{$type} class=\"{$class}\">", $html);
        $this->assertStringContainsString('from-cms', $html);
    }

    /**
     * @dataProvider chromeProvider
     */
    public function testFallsBackWhenNoPartialExists(string $type, string $class): void
    {
        $html = $this->render($type, ["{$type}_partial" => null]);

        $this->assertStringContainsString("<{$type} class=\"{$class}\">", $html);
        $this->assertStringContainsString('Example Site', $html);
    }

    /**
     * @dataProvider chromeProvider
     */
    public function testRendersNothingWhenPageDisablesIt(string $type, string $class): void
    {
        $html = $this->render($type, [
            "{$type}_partial" => null,
            "{$type}_partial_disabled" => true,
        ]);

        $this->assertSame('', trim($html));
    }

    public function testHeaderFallbackRendersPrimaryMenu(): void
    {
        $html = $this->render('header', ['header_partial' => null]);

        $this->assertStringContainsString('class="menu"', $html);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $type, array $context): string
    {
        return $this->twig->render("{$type}.twig", $context + [
            'site' => ['url' => 'https://example.com/', 'name' => 'Example Site'],
        ]);
    }
}
