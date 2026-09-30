<?php

declare(strict_types=1);

namespace Osmium\Services\CookieConsent\Controllers;

use const Osmium\Config\PAGES;
use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Modules\Admin\Services\OsmiumAdmin;
use Osmium\Services\CookieConsent\Models\CookieConsentConfig;

/**
 * Cookie consent settings controller - a full-page form POST/redirect flow
 * (not the AJAX pattern other services use), matching how the hardcoded
 * settings page it was extracted from already worked.
 *
 * Routes:
 *   - index() → /admin/settings/cookie-consent/  (GET shows the form, POST saves it)
 */
class CookieConsentController extends AdminController
{
    private const CONFIG_FILE_PATH = 'app/config/services/cookie-consent.json.php';
    private const DEFAULT_CONFIG = <<<'JSON'
        <?php exit(); ?>
        {
            "cookieConsent": {
                "enabled": false,
                "cookieName": "osmium_cookie_consent",
                "privacyUrl": "/privacy",
                "colors": {
                    "accent": "#0d6efd",
                    "modalBg": "#1a1a1a"
                },
                "copy": {
                    "bannerText": "We use cookies to run this site and, with your consent, to measure site usage. See our <a href=\"{privacyUrl}\">Privacy Notice</a> for details.",
                    "statusText": "Cookies are currently {status}. See our <a href=\"{privacyUrl}\">Privacy Notice</a> for details.",
                    "acceptLabel": "Accept",
                    "rejectLabel": "Reject",
                    "ribbonAriaLabel": "View cookie consent status",
                    "ribbonTitle": "Cookie preferences"
                }
            }
        }
        JSON;

    public function index(): void
    {
        $isPost = $this->isPost();
        if ($isPost) $this->handleSubmit();

        $config = (array) CookieConsentConfig::get();
        $this->data['admin']['config']['cookieConsent'] = $this->configToArray($config);
        $this->data['admin']['pages'] = $this->fetchPagesForDropdown();
        $this->data['admin']['privacyUrlDisplay'] = $this->resolvePrivacyUrlDisplay(
            $this->data['admin']['config']['cookieConsent']['privacyUrl'] ?? '',
        );
        $this->data['admin']['settingsSaved'] = $_SESSION['cookie_consent_settings_saved'] ?? false;
        $this->data['admin']['settingsError'] = $_SESSION['cookie_consent_settings_error'] ?? false;
        unset($_SESSION['cookie_consent_settings_saved'], $_SESSION['cookie_consent_settings_error']);

        $this->setView('cookie-consent/index.phtml');
    }

    private function handleSubmit(): void
    {
        $csrfValid = $this->admin->auth->validateCsrf();
        if (!$csrfValid) {
            $_SESSION['cookie_consent_settings_error'] = 'Invalid form submission. Please try again.';
            $this->redirect('settings/cookie-consent/');
        }

        $current = $this->configToArray((array) CookieConsentConfig::get());

        $enabled = isset($_POST['enabled']);

        $cookieName = \trim($_POST['cookie_name'] ?? ($current['cookieName'] ?? ''));
        $cookieNameValid = (bool) \preg_match('/^[A-Za-z0-9_]+$/', $cookieName);
        if (!$cookieNameValid) {
            $_SESSION['cookie_consent_settings_error'] = 'Cookie name must contain only letters, numbers, and underscores - not saved.';
            $cookieName = $current['cookieName'] ?? '';
        }

        $privacyUrl = \trim($_POST['privacy_url'] ?? ($current['privacyUrl'] ?? ''));
        $privacyPageId = (int) ($_POST['privacy_page_id'] ?? 0);
        if ($privacyPageId > 0) {
            $pagesById = \array_column(array: PAGES, column_key: null, index_key: 'id');
            $selectedPage = $pagesById[$privacyPageId] ?? null;
            if ($selectedPage) $privacyUrl = '/' . $selectedPage['url'] . '/';
        }

        $accent = \trim($_POST['color_accent'] ?? ($current['colors']['accent'] ?? ''));
        $accentValid = (bool) \preg_match('/^#[0-9a-fA-F]{6}$/', $accent);
        if (!$accentValid) {
            $_SESSION['cookie_consent_settings_error'] = 'Accent color must be a hex value like #0d6efd - not saved.';
            $accent = $current['colors']['accent'] ?? '';
        }

        $modalBg = \trim($_POST['color_modal_bg'] ?? ($current['colors']['modalBg'] ?? ''));
        $modalBgValid = (bool) \preg_match('/^#[0-9a-fA-F]{6}$/', $modalBg);
        if (!$modalBgValid) {
            $_SESSION['cookie_consent_settings_error'] = 'Modal background color must be a hex value like #1a1a1a - not saved.';
            $modalBg = $current['colors']['modalBg'] ?? '';
        }

        $bannerText = \trim($_POST['banner_text'] ?? ($current['copy']['bannerText'] ?? ''));
        $statusText = \trim($_POST['status_text'] ?? ($current['copy']['statusText'] ?? ''));
        $acceptLabel = \trim($_POST['accept_label'] ?? ($current['copy']['acceptLabel'] ?? ''));
        $rejectLabel = \trim($_POST['reject_label'] ?? ($current['copy']['rejectLabel'] ?? ''));
        $ribbonAriaLabel = \trim($_POST['ribbon_aria_label'] ?? ($current['copy']['ribbonAriaLabel'] ?? ''));
        $ribbonTitle = \trim($_POST['ribbon_title'] ?? ($current['copy']['ribbonTitle'] ?? ''));

        $this->saveConfig([
            'enabled' => $enabled,
            'cookieName' => $cookieName,
            'privacyUrl' => $privacyUrl,
            'colors' => [
                'accent' => $accent,
                'modalBg' => $modalBg,
            ],
            'copy' => [
                'bannerText' => $bannerText,
                'statusText' => $statusText,
                'acceptLabel' => $acceptLabel,
                'rejectLabel' => $rejectLabel,
                'ribbonAriaLabel' => $ribbonAriaLabel,
                'ribbonTitle' => $ribbonTitle,
            ],
        ]);

        $this->admin->model->changelog->log(
            description: 'Updated cookie consent settings',
            recordType: 'settings',
        );

        CookieConsentConfig::clearCache();

        $_SESSION['cookie_consent_settings_saved'] = true;
        $this->redirect('settings/cookie-consent/');
    }

    private function saveConfig(array $cookieConsent): void
    {
        $configExists = \file_exists(self::CONFIG_FILE_PATH);
        if (!$configExists) $this->ensureConfigDirectoryExists();

        $newJson = \json_encode(
            value: ['cookieConsent' => $cookieConsent],
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, "<?php exit(); ?>\n" . $newJson . "\n");
    }

    private function ensureConfigDirectoryExists(): void
    {
        $dir = \dirname(self::CONFIG_FILE_PATH);
        $alreadyExists = \is_dir($dir);
        if (!$alreadyExists) \mkdir(directory: $dir, permissions: 0755, recursive: true);
    }

    /**
     * Pages available to link the Privacy Notice to, same filtering as
     * Menus'/the legacy Settings page's picker (skip deleted and
     * admin-internal pages).
     */
    private function fetchPagesForDropdown(): array
    {
        $pages = [];
        foreach (PAGES as $route) {
            $page = $this->routeToPageOption($route);
            if ($page) $pages[] = $page;
        }
        \usort(array: $pages, callback: fn($a, $b) => \strcmp(string1: $a['url'], string2: $b['url']));
        return $pages;
    }

    private function routeToPageOption(array $route): ?array
    {
        $isAdminRoute = isset($route['id']) && (int) $route['id'] >= OsmiumAdmin::ADMIN_PAGE_ID_THRESHOLD;
        $isDeleted = $route['deleted_at'] !== null;

        $shouldSkip = $isAdminRoute || $isDeleted;
        if ($shouldSkip) return null;

        return [
            'id' => $route['id'],
            'url' => $route['url'],
            'title' => $route['title'] ?? '',
        ];
    }

    /**
     * Pre-fill display for the Privacy Notice search field: "/url/ - Title"
     * when the stored value matches a real page, or the raw stored value
     * unchanged when it's a custom/external URL (or matches nothing).
     */
    private function resolvePrivacyUrlDisplay(string $url): string
    {
        $slug = \trim($url, '/');
        $page = PAGES[$slug] ?? null;
        if (!$page) return $url;

        return '/' . $page['url'] . '/ - ' . ($page['title'] ?? '');
    }

    /**
     * CookieConsentConfig::get() returns nested stdClass objects; the
     * controller/view work with plain arrays throughout, matching how
     * every other settings page's $admin['config'] is shaped.
     */
    private function configToArray(array $config): array
    {
        return \json_decode(\json_encode($config), associative: true);
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
