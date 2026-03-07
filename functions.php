<?php
/**
 * TailPress Base Theme - Functions
 *
 * A clean WordPress theme base with:
 * - Tailwind CSS v4 + Alpine.js + Vite
 * - ACF Flexible Content auto-loading block system
 * - Gutenberg/block editor fully disabled
 * - Alpine.js powered navigation with mobile drill-down menu
 *
 * @package TailPress
 */

// Core includes
include __DIR__ . '/includes/utils.php';
include __DIR__ . '/includes/image-helpers.php';
include __DIR__ . '/includes/theme-setup.php';
include __DIR__ . '/includes/content-builder.php';
include __DIR__ . '/includes/nav.php';

// Load TailPress Framework
if (is_file(__DIR__ . '/vendor/autoload_packages.php')) {
    require_once __DIR__ . '/vendor/autoload_packages.php';
}

/**
 * Helper function to get Vite dev server URL for DDEV environments
 */
function get_vite_dev_server_url() {
    if (getenv('IS_DDEV_PROJECT') == 'true') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/:\d+$/', '', $host);
        return "{$scheme}://{$host}:3000";
    }
    return null;
}

/**
 * Initialize the TailPress theme
 */
function tailpress(): TailPress\Framework\Theme
{
    return TailPress\Framework\Theme::instance()
        ->assets(fn($manager) => $manager
            ->withCompiler(
                new TailPress\Framework\Assets\ViteCompiler(get_vite_dev_server_url(), null, true, true),
                fn($compiler) => $compiler
                    ->registerAsset('resources/css/app.css')
                    ->registerAsset('resources/js/modules.js')
                    ->editorStyleFile('resources/css/editor-style.css')
            )
            ->enqueueAssets()
        )
        ->menus(fn($manager) => $manager
            ->add('primary', 'Primary Menu')
            ->add('footer_1', 'Footer Menu 1')
            ->add('footer_2', 'Footer Menu 2')
        )
        ->themeSupport(fn($manager) => $manager->add([
            'title-tag',
            'custom-logo',
            'post-thumbnails',
            'html5' => [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            ]
        ]));
}

tailpress();
