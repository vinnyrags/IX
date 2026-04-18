<?php

declare(strict_types=1);

namespace IX\Services;

/**
 * Collects and serializes JSON-LD structured data for SEO.
 *
 * A newable utility — construct directly with `new SchemaBuilderService()`.
 * Provides generic schema collection and common helpers (person, organization,
 * breadcrumbs). Post-type-specific schema (BlogPosting, CreativeWork, etc.)
 * is built by the owning provider and added via `add()`.
 *
 * Usage:
 *   $schema = new SchemaBuilderService();
 *   $schema->add([
 *       '@context' => 'https://schema.org',
 *       '@type' => 'BlogPosting',
 *       'headline' => $post->title(),
 *       'author' => $schema->person(),
 *       ...
 *   ]);
 *   $schema->breadcrumbList([...]);
 *   $context['schema'] = $schema->toJson();
 *
 * In Twig:
 *   {% if schema is defined %}
 *       <script type="application/ld+json">{{ schema | raw }}</script>
 *   {% endif %}
 */
class SchemaBuilderService
{
    private array $schemas = [];

    private string $siteUrl;
    private string $authorName;

    public function __construct(string $authorName = 'Vincent Ragosta', ?string $siteUrl = null)
    {
        $this->authorName = $authorName;
        $this->siteUrl = $siteUrl ?? home_url('/');
    }

    /**
     * Add a schema object to the collection.
     */
    public function add(array $schema): self
    {
        $this->schemas[] = $schema;
        return $this;
    }

    /**
     * Add BreadcrumbList schema.
     *
     * @param array<array{label: string, url?: string}> $crumbs
     */
    public function breadcrumbList(array $crumbs): self
    {
        $items = [];
        foreach ($crumbs as $index => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['label'],
            ];
            if (isset($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            $items[] = $item;
        }

        $this->schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];

        return $this;
    }

    /**
     * Build a Person schema fragment.
     */
    public function person(): array
    {
        return [
            '@type' => 'Person',
            'name' => $this->authorName,
            'url' => $this->siteUrl,
        ];
    }

    /**
     * Build an Organization schema fragment.
     */
    public function organization(): array
    {
        return [
            '@type' => 'Organization',
            'name' => $this->authorName,
            'url' => $this->siteUrl,
        ];
    }

    /**
     * Output all collected schemas as a JSON string.
     *
     * Returns a single object if one schema, or an array if multiple.
     */
    public function toJson(): string
    {
        if (empty($this->schemas)) {
            return '';
        }

        $data = count($this->schemas) === 1
            ? $this->schemas[0]
            : $this->schemas;

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}
