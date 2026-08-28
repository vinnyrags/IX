<?php

namespace IX\Tests\Unit\Providers\Theme\Hooks;

use IX\Providers\Theme\Hooks\MailIdentity;
use Mythus\Contracts\Hook;
use WorDBless\BaseTestCase;

/**
 * Two behaviours matter here, and neither is "does it set the address".
 *
 * 1. The hook must be INERT when unconfigured. It ships enabled to every
 *    Mythus/IX site, so an unconfigured install must behave exactly as it did
 *    before the hook existed.
 * 2. It must not clobber an explicit per-send From. wp_mail() applies
 *    wp_mail_from even when the caller supplied a From: header, so a naive
 *    filter would silently rewrite purpose-built per-send identities.
 */
class MailIdentityTest extends BaseTestCase
{
    private MailIdentity $hook;

    public function setUp(): void
    {
        parent::setUp();
        $this->hook = new MailIdentity();
    }

    private function coreDefault(): string
    {
        $host = preg_replace('/^www\./i', '', parse_url(network_home_url(), PHP_URL_HOST) ?: '');

        return 'wordpress@' . $host;
    }

    public function testImplementsHook(): void
    {
        $this->assertInstanceOf(Hook::class, $this->hook);
    }

    /**
     * The safety property: unconfigured means untouched.
     */
    public function testIsInertWhenNotConfigured(): void
    {
        $default = $this->coreDefault();

        $this->assertSame($default, $this->hook->filterFromEmail($default));
        $this->assertSame('WordPress', $this->hook->filterFromName('WordPress'));
    }

    public function testReplacesCoreDefaultWhenConfiguredViaFilter(): void
    {
        add_filter(MailIdentity::FILTER_FROM, static fn (): string => 'noreply@example.com');
        add_filter(MailIdentity::FILTER_FROM_NAME, static fn (): string => 'Example');

        $this->assertSame('noreply@example.com', $this->hook->filterFromEmail($this->coreDefault()));
        $this->assertSame('Example', $this->hook->filterFromName('WordPress'));
    }

    /**
     * The regression that matters: a per-send From must survive.
     *
     * @dataProvider explicitAddresses
     */
    public function testLeavesExplicitAddressAlone(string $address): void
    {
        add_filter(MailIdentity::FILTER_FROM, static fn (): string => 'noreply@example.com');

        $this->assertSame($address, $this->hook->filterFromEmail($address));
    }

    public static function explicitAddresses(): array
    {
        return [
            'a per-send brand identity' => ['noreply@itzenzo.tv'],
            'a plugin-set address' => ['forms@example.org'],
            'another site default' => ['wordpress@some-other-host.test'],
        ];
    }

    public function testLeavesExplicitNameAlone(): void
    {
        add_filter(MailIdentity::FILTER_FROM_NAME, static fn (): string => 'Example');

        $this->assertSame('itzenzoTTV', $this->hook->filterFromName('itzenzoTTV'));
    }

    /**
     * A misconfigured constant must not produce a broken From header.
     */
    public function testIgnoresAnInvalidConfiguredAddress(): void
    {
        add_filter(MailIdentity::FILTER_FROM, static fn (): string => 'not-an-email');

        $default = $this->coreDefault();
        $this->assertSame($default, $this->hook->filterFromEmail($default));
    }

    public function testRegistersBothFilters(): void
    {
        $this->hook->register();

        $this->assertNotFalse(has_filter('wp_mail_from', [$this->hook, 'filterFromEmail']));
        $this->assertNotFalse(has_filter('wp_mail_from_name', [$this->hook, 'filterFromName']));
    }
}
