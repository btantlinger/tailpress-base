<?php
/**
 * Theme Setup - Image sizes, Gutenberg removal, hero preloading
 *
 * @package TailPress
 */

/**
 * Add custom image sizes for hero sections
 */
add_action('after_setup_theme', 'wm_add_custom_image_sizes');
function wm_add_custom_image_sizes() {
    add_image_size('hero-small', 768, 500, true);
    add_image_size('hero-medium', 1200, 600, true);
    add_image_size('hero-large', 1920, 800, true);
    add_image_size('hero-xlarge', 2560, 1000, true);
}

/**
 * Make custom sizes available in ACF and media library
 */
add_filter('image_size_names_choose', 'wm_add_custom_sizes_to_media_chooser');
function wm_add_custom_sizes_to_media_chooser($sizes) {
    return array_merge($sizes, [
        'hero-small'  => __('Hero Small (768x500)'),
        'hero-medium' => __('Hero Medium (1200x600)'),
        'hero-large'  => __('Hero Large (1920x800)'),
        'hero-xlarge' => __('Hero XLarge (2560x1000)'),
    ]);
}

/**
 * Add custom sizes to srcset calculations
 */
add_filter('wp_calculate_image_srcset', 'wm_add_custom_sizes_to_srcset', 10, 5);
function wm_add_custom_sizes_to_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    $custom_sizes = ['hero-small', 'hero-medium', 'hero-large', 'hero-xlarge'];
    $base_url = dirname($image_src) . '/';

    foreach ($custom_sizes as $size_name) {
        if (isset($image_meta['sizes'][$size_name])) {
            $size_data = $image_meta['sizes'][$size_name];
            $sources[$size_data['width']] = [
                'url'        => $base_url . $size_data['file'],
                'descriptor' => 'w',
                'value'      => $size_data['width'],
            ];
        }
    }
    return $sources;
}

/**
 * =============================================
 * DISABLE GUTENBERG / BLOCK EDITOR COMPLETELY
 * =============================================
 */

// Remove block CSS from frontend
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('global-styles');

    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
        wp_dequeue_style('dashicons');
    }
}, 100);

// Remove block CSS from admin
add_action('admin_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
}, 100);

// Disable block-based widgets
add_filter('gutenberg_use_widgets_block_editor', '__return_false');
add_filter('use_widgets_block_editor', '__return_false');

// Remove block editor scripts from admin
add_action('admin_init', function () {
    remove_action('admin_enqueue_scripts', 'wp_common_block_scripts_and_styles');
});

/**
 * Preload hero image for pages with page_content containing a hero block
 */
add_action('wp_head', function () {
    if (!is_singular()) {
        return;
    }

    $page_id = get_the_ID();
    if (!$page_id) {
        return;
    }

    $page_content = get_field('page_content', $page_id);
    if (!$page_content || !is_array($page_content)) {
        return;
    }

    foreach ($page_content as $block) {
        if (($block['acf_fc_layout'] ?? '') === 'hero' && isset($block['hero_image']['id'])) {
            $mobile  = wp_get_attachment_image_url($block['hero_image']['id'], 'hero-small');
            $desktop = wp_get_attachment_image_url($block['hero_image']['id'], 'hero-large');

            if ($mobile && $desktop) {
                echo '<link rel="preload" as="image" href="' . esc_url($mobile) . '" media="(max-width: 768px)" fetchpriority="high">' . "\n";
                echo '<link rel="preload" as="image" href="' . esc_url($desktop) . '" media="(min-width: 769px)" fetchpriority="high">' . "\n";
            }
            break;
        }
    }
});

/**
 * Template shortcode - load template parts via shortcode
 * Usage: [template name="my-template"]
 */
function wm_template_part_shortcode($atts) {
    $raw_atts = (array) $atts;
    $atts = shortcode_atts(['name' => '', 'slug' => ''], $raw_atts);

    if (empty($atts['name'])) {
        return '<!-- Template shortcode error: name parameter is required -->';
    }

    $path_parts = [];
    foreach (explode('/', $atts['name']) as $part) {
        $path_parts[] = sanitize_file_name($part);
    }

    $template_name = implode('/', $path_parts);
    $template_slug = !empty($atts['slug']) ? sanitize_file_name($atts['slug']) : null;
    $template_path = 'template-parts/' . $template_name;

    if (!str_starts_with($template_path, 'template-parts/')) {
        return '<!-- Template shortcode error: invalid template path -->';
    }

    if (!locate_template($template_path . '.php', false)) {
        return '<!-- Template not found: ' . esc_html($template_path) . '.php -->';
    }

    $reserved_keys = ['name', 'slug'];
    $args = array_diff_key($raw_atts, array_flip($reserved_keys));

    ob_start();
    get_template_part($template_path, $template_slug, $args);
    return ob_get_clean();
}
add_shortcode('template', 'wm_template_part_shortcode');
