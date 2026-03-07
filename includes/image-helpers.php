<?php
/**
 * Image helper functions
 *
 * @package TailPress
 */

/**
 * Get structured image data from an attachment ID
 */
function wm_get_image_data_array(int $attachment_id, string $large_size = 'large', string $thumb_size = 'thumbnail'): false|array {
    if (!wp_attachment_is_image($attachment_id)) {
        return false;
    }

    return [
        'id'        => $attachment_id,
        'url'       => wp_get_attachment_image_url($attachment_id, $large_size),
        'thumbnail' => wp_get_attachment_image_url($attachment_id, $thumb_size),
        'alt'       => get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: get_the_title($attachment_id),
        'caption'   => wp_get_attachment_caption($attachment_id),
        'title'     => get_the_title($attachment_id),
    ];
}

/**
 * Generate responsive image tag
 */
function wm_responsive_image_tag(int $id, string $requested_size, array $attr = [], string $context = 'default'): string {
    if (!wp_attachment_is_image($id)) {
        return '';
    }

    return wp_get_attachment_image($id, $requested_size, false, $attr);
}

/**
 * Generate an image tag from an ID or URL
 */
function wm_get_image_tag($image, $classes = '', string $size = 'full', bool $skip_lazy = false, array $atts = []): string {
    $image_id = null;

    if (is_numeric($image)) {
        $image_id = absint($image);
    } elseif (is_string($image)) {
        $image_id = attachment_url_to_postid($image);
    }

    if (empty($image_id)) {
        return '';
    }

    if (is_array($classes)) {
        $classes = implode(' ', $classes);
    }

    if ($skip_lazy || str_contains($classes, 'skip-lazy')) {
        $classes .= ' skip-lazy';
    }

    $atts = array_merge(['class' => $classes], $atts);
    $atts = array_filter($atts);

    return wp_get_attachment_image($image_id, $size, false, $atts);
}
