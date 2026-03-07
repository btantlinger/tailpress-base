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

// Move jQuery to footer (frontend only)
add_action('wp_enqueue_scripts', function() {
	if (!is_admin()) {
		wp_scripts()->add_data('jquery', 'group', 1);
		wp_scripts()->add_data('jquery-core', 'group', 1);
		wp_scripts()->add_data('jquery-migrate', 'group', 1);
	}
}, 999);

// Defer jQuery scripts (frontend only)
add_filter('script_loader_tag', function($tag, $handle) {
	if (is_admin()) {
		return $tag;
	}

	$defer_handles = ['jquery', 'jquery-core', 'jquery-migrate'];

	if (in_array($handle, $defer_handles)) {
		if (strpos($tag, 'defer') === false) {
			$tag = str_replace('<script', '<script defer', $tag);
		}
	}

	return $tag;
}, 10, 2);

// Move TailPress scripts to footer
add_action('wp_enqueue_scripts', function() {
	if (!is_admin()) {
		wp_script_add_data('tailpress-modules', 'group', 1);
		wp_script_add_data('tailpress-app', 'group', 1);
	}
}, 999);

// Force module type on modules.js
add_filter('script_loader_tag', function($tag, $handle, $src) {
	if (is_admin()) {
		return $tag;
	}

	if ($handle === 'tailpress-modules') {
		return str_replace('type="text/javascript"', 'type="module"', $tag);
	}
	return $tag;
}, 10, 3);
