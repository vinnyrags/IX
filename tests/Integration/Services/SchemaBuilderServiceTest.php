<?php

namespace IX\Tests\Integration\Services;

use IX\Services\SchemaBuilderService;
use WorDBless\BaseTestCase;

/**
 * Integration tests for SchemaBuilderService's author default.
 */
class SchemaBuilderServiceTest extends BaseTestCase
{
    public function testAuthorDefaultsToSiteTitle(): void
    {
        update_option('blogname', 'Example Site');

        $schema = new SchemaBuilderService();

        $this->assertSame('Example Site', $schema->person()['name']);
        $this->assertSame('Example Site', $schema->organization()['name']);
    }

    public function testExplicitAuthorWins(): void
    {
        update_option('blogname', 'Example Site');

        $schema = new SchemaBuilderService('Jane Doe', 'https://example.com/');

        $this->assertSame('Jane Doe', $schema->person()['name']);
        $this->assertSame('https://example.com/', $schema->person()['url']);
    }
}
