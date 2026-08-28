<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Hooks;

use Mythus\Contracts\Hook;

/**
 * Gives outbound mail a From address on the site's own domain.
 *
 * WordPress defaults to `wordpress@<sitename>`, which no relay is authorised
 * to send as. What happens next depends on the relay and both outcomes are
 * bad: Gmail silently REWRITES the From to the authenticated account, so a
 * password reset arrives from a stranger's mailbox and reads as phishing;
 * Resend and most transactional providers REFUSE the message outright because
 * the sender is not on a verified domain.
 *
 * ---------------------------------------------------------------------------
 * CONFIGURATION — this hook is INERT until configured.
 *
 * Set per environment in that environment's gitignored wp-config-env.php:
 *
 *     define('IX_MAIL_FROM', 'noreply@example.com');
 *     define('IX_MAIL_FROM_NAME', 'Example');       // optional
 *
 * or filter it:
 *
 *     add_filter('ix/mail_from', fn() => 'noreply@example.com');
 *
 * Deliberately NOT derived from the site host. A staging install at
 * staging.example.com would produce noreply@staging.example.com — a subdomain
 * the provider has almost certainly not verified — so every staging email
 * would be refused, by a "helpful" default nobody configured. Per-environment
 * config is explicit and cannot guess wrong.
 * ---------------------------------------------------------------------------
 *
 * IMPORTANT — why this checks the incoming value instead of returning a
 * constant. `wp_mail()` applies the `wp_mail_from` filter *even when the
 * caller passed an explicit `From:` header*, so an unconditional filter would
 * silently clobber every per-send identity on the site. Child themes set
 * per-send From headers deliberately (a shop sending as its own brand, for
 * example); overriding those here would undo purpose-built code. So we only
 * substitute when the value is still WordPress's own default.
 */
class MailIdentity implements Hook
{
    public const FILTER_FROM = 'ix/mail_from';

    public const FILTER_FROM_NAME = 'ix/mail_from_name';

    /** WordPress's own default display name — the only one we replace. */
    private const CORE_DEFAULT_NAME = 'WordPress';

    public function register(): void
    {
        add_filter('wp_mail_from', [$this, 'filterFromEmail']);
        add_filter('wp_mail_from_name', [$this, 'filterFromName']);
    }

    /**
     * Replace only WordPress's `wordpress@<sitename>` default, so an explicit
     * per-send `From:` header survives untouched.
     */
    public function filterFromEmail(string $from): string
    {
        $configured = $this->configuredFrom();

        if ($configured === '' || $from !== $this->coreDefaultEmail()) {
            return $from;
        }

        return $configured;
    }

    /**
     * Same rule for the display name. wp_mail() applies this filter
     * independently of the address one, so a per-send header that set only an
     * address still gets a sensible name.
     */
    public function filterFromName(string $name): string
    {
        $configured = $this->configuredFromName();

        if ($configured === '' || $name !== self::CORE_DEFAULT_NAME) {
            return $name;
        }

        return $configured;
    }

    private function configuredFrom(): string
    {
        $from = defined('IX_MAIL_FROM') ? (string) IX_MAIL_FROM : '';
        $from = (string) apply_filters(self::FILTER_FROM, $from);

        return is_email($from) ? $from : '';
    }

    private function configuredFromName(): string
    {
        $name = defined('IX_MAIL_FROM_NAME') ? (string) IX_MAIL_FROM_NAME : '';

        return trim((string) apply_filters(self::FILTER_FROM_NAME, $name));
    }

    /**
     * Rebuild the address wp_mail() would have used, matching core's logic in
     * wp-includes/pluggable.php: the site host with any leading `www.` removed.
     */
    private function coreDefaultEmail(): string
    {
        $host = parse_url(network_home_url(), PHP_URL_HOST) ?: '';

        return 'wordpress@' . preg_replace('/^www\./i', '', $host);
    }
}
