<?php

declare(strict_types=1);

namespace Osmium\Services\CookieConsent\Models;

/**
 * Cookie consent configuration helper.
 *
 * Loads this service's own settings file so theme blocks don't hardcode
 * the cookie name, privacy URL, colors, or copy, matching the file-based
 * config convention used by Xero/Stripe/PayPal/Analytics
 * (app/config/services/{id}.json.php).
 */
class CookieConsentConfig
{
    private static ?object $config = null;
    private static string $configPath = 'app/config/services/cookie-consent.json.php';

    /**
     * Falls back to defaults (disabled) if the config file is missing, so
     * installing this service stays inert until someone visits its settings
     * page and enables it.
     */
    public static function get(): object
    {
        $configLoaded = self::$config !== null;
        if ($configLoaded) return self::$config;

        $configFile = self::$configPath;

        $configExists = \file_exists($configFile);
        if (!$configExists) {
            self::$config = self::defaults();
            return self::$config;
        }

        $content = \file_get_contents($configFile);
        $jsonStart = \strpos(haystack: $content, needle: '{');

        $noJsonFound = $jsonStart === false;
        if ($noJsonFound) {
            self::$config = self::defaults();
            return self::$config;
        }

        $json = \substr(string: $content, offset: $jsonStart);
        $decoded = \json_decode($json);

        self::$config = $decoded->cookieConsent ?? self::defaults();

        return self::$config;
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    private static function defaults(): object
    {
        return (object) [
            'enabled' => false,
            'cookieName' => 'osmium_cookie_consent',
            'privacyUrl' => '/privacy',
            'colors' => (object) [
                'accent' => '#0d6efd',
                'modalBg' => '#1a1a1a',
            ],
            'copy' => (object) [
                'bannerText' => 'We use cookies to run this site and, with your consent, to measure site usage. See our <a href="{privacyUrl}">Privacy Notice</a> for details.',
                'statusText' => 'Cookies are currently {status}. See our <a href="{privacyUrl}">Privacy Notice</a> for details.',
                'acceptLabel' => 'Accept',
                'rejectLabel' => 'Reject',
                'ribbonAriaLabel' => 'View cookie consent status',
                'ribbonTitle' => 'Cookie preferences',
            ],
        ];
    }
}
