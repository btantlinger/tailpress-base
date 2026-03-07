<?php
/**
 * Global utility functions for the theme.
 *
 * All functions prefixed with "wm_" (WebMoves) to avoid conflicts.
 *
 * @package TailPress
 */

/**
 * Generate responsive background image CSS using image-set
 */
function wm_responsive_bg_css($image_array, $overlay_opacity = 40, $fallback_size = 'hero-medium') {
    if (!$image_array || !isset($image_array['sizes'])) {
        return '';
    }

    $sizes = $image_array['sizes'];
    $image_set_parts = [];

    if (isset($sizes['hero-small']))  $image_set_parts[] = "url('" . esc_url($sizes['hero-small']) . "') 1x";
    if (isset($sizes['hero-medium'])) $image_set_parts[] = "url('" . esc_url($sizes['hero-medium']) . "') 1.5x";
    if (isset($sizes['hero-large']))  $image_set_parts[] = "url('" . esc_url($sizes['hero-large']) . "') 2x";
    if (isset($sizes['hero-xlarge'])) $image_set_parts[] = "url('" . esc_url($sizes['hero-xlarge']) . "') 3x";

    $fallback_url = $sizes[$fallback_size] ?? $sizes['hero-medium'] ?? $sizes['large'] ?? '';

    $bg_css = !empty($image_set_parts)
        ? "background-image: image-set(" . implode(', ', $image_set_parts) . "); background-image: url('" . esc_url($fallback_url) . "');"
        : "background-image: url('" . esc_url($fallback_url) . "');";

    $bg_css .= " --overlay-opacity: " . ($overlay_opacity / 100) . ";";

    return $bg_css;
}

/**
 * Get text color class based on background color darkness
 */
function wm_get_text_color_for_background($bg_class, $options = []) {
    $defaults = ['prefer_white' => false, 'high_contrast' => false];
    $options = array_merge($defaults, $options);

    if (empty($bg_class) || !preg_match('/^bg-(.+?)(?:-(\d+))?$/', $bg_class, $matches)) {
        return '';
    }

    $color_name = $matches[1];
    $shade = isset($matches[2]) ? (int) $matches[2] : 500;
    $darkness = wm_calculate_color_darkness($color_name, $shade);

    if ($options['high_contrast']) {
        return $darkness >= 6 ? 'text-white' : 'text-black';
    }
    return ($options['prefer_white'] ? $darkness >= 4 : $darkness >= 5) ? 'text-white' : 'text-gray-900';
}

/**
 * Calculate relative darkness of a color (0-10 scale)
 */
function wm_calculate_color_darkness($color_name, $shade) {
    $base_darkness = [
        'white' => 0, 'yellow' => 1, 'light' => 1, 'amber' => 2, 'orange' => 2,
        'lime' => 2, 'cyan' => 2, 'sky' => 2, 'pink' => 2, 'secondary' => 3,
        'gray' => 3, 'grey' => 3, 'slate' => 3, 'zinc' => 3, 'red' => 3,
        'green' => 3, 'blue' => 3, 'indigo' => 3, 'purple' => 3, 'tertiary' => 3,
        'primary' => 4, 'dark' => 5, 'black' => 5,
    ];

    $base = $base_darkness[$color_name] ?? 3;

    if ($shade <= 100) $shade_adj = -1;
    elseif ($shade <= 300) $shade_adj = 0;
    elseif ($shade <= 500) $shade_adj = 1;
    elseif ($shade <= 700) $shade_adj = 2;
    elseif ($shade <= 800) $shade_adj = 3;
    else $shade_adj = 4;

    return max(0, min(10, $base + $shade_adj));
}

/**
 * Parse markdown-style **bold** emphasis into styled spans
 */
function wm_parse_md_emphasis($content, $text_cls = 'text-secondary-500', $em_to_block = false) {
    if ($em_to_block) {
        $text_cls .= " block";
    }
    return preg_replace('/\*\*(.*?)\*\*/', '<span class="' . $text_cls . '">$1</span>', $content);
}

/**
 * Get block styling from ACF sub_fields (spacing, color, CSS ID/classes)
 */
function wm_get_block_styling($default_classes = '') {
    $css_id = get_sub_field('css_id') ?: '';
    $css_classes = get_sub_field('css_classes') ?: '';

    // Base spacing
    $spacing_group = get_sub_field('spacing');
    if ($spacing_group && is_array($spacing_group)) {
        $padding_y = $spacing_group['padding_y'] ?? '';
        $margin_y = $spacing_group['margin_y'] ?? '';
    } else {
        $padding_y = get_sub_field('padding_y') ?: '';
        $margin_y = get_sub_field('margin_y') ?: '';
    }

    // Responsive spacing
    $responsive_classes = [];
    if ($spacing_group && is_array($spacing_group)) {
        foreach (['sm', 'md', 'lg', 'xl'] as $bp) {
            $responsive_classes[] = $spacing_group["padding_y_{$bp}"] ?? '';
            $responsive_classes[] = $spacing_group["margin_y_{$bp}"] ?? '';
        }
    } else {
        foreach (['sm', 'md', 'lg', 'xl'] as $bp) {
            $responsive_classes[] = get_sub_field("padding_y_{$bp}") ?: '';
            $responsive_classes[] = get_sub_field("margin_y_{$bp}") ?: '';
        }
    }
    $responsive_classes = trim(implode(' ', array_filter($responsive_classes)));

    // Color fields
    $bg_color = '';
    $text_color = '';
    $color_group = get_sub_field('color');

    if ($color_group && is_array($color_group)) {
        if (isset($color_group['bg_color_group']) && is_array($color_group['bg_color_group'])) {
            $name = $color_group['bg_color_group']['bg_color_name'] ?? '';
            $shade = $color_group['bg_color_group']['bg_color_shade'] ?? '';
            if ($name) {
                $bg_color = in_array($name, ['white', 'black']) ? "bg-{$name}" : "bg-{$name}-" . ($shade ?: '500');
            }
        }
        if (isset($color_group['text_color_group']) && is_array($color_group['text_color_group'])) {
            $name = $color_group['text_color_group']['text_color_name'] ?? '';
            $shade = $color_group['text_color_group']['text_color_shade'] ?? '';
            if ($name) {
                $text_color = in_array($name, ['white', 'black']) ? "text-{$name}" : "text-{$name}-" . ($shade ?: '500');
            }
        }
    }

    // Strip default classes that conflict with style tab values
    if ($default_classes) {
        $default_class_list = array_filter(explode(' ', $default_classes), function ($cls) use ($padding_y, $margin_y, $bg_color, $text_color) {
            if ($padding_y && preg_match('/^py-/', $cls)) return false;
            if ($margin_y && preg_match('/^my-/', $cls)) return false;
            if ($bg_color && preg_match('/^bg-/', $cls)) return false;
            if ($text_color && preg_match('/^text-/', $cls)) return false;
            return true;
        });
        $default_classes = implode(' ', $default_class_list);
    }

    $classes = trim("$default_classes $padding_y $margin_y $responsive_classes $bg_color $text_color $css_classes");

    $attributes = [];
    if ($css_id) {
        $attributes['id'] = 'id="' . esc_attr($css_id) . '"';
    }
    if ($classes) {
        $attributes['class'] = 'class="' . esc_attr($classes) . '"';
    }

    return $attributes;
}

/**
 * Convert attributes array to HTML string
 */
function wm_get_attributes_string($attributes): string {
    return empty($attributes) ? '' : implode(' ', array_filter($attributes));
}

/**
 * Get block attributes as a string, combining styling fields with custom attributes
 */
function wm_get_block_attributes($default_classes = '', $custom_attributes = ''): string {
    $styling_attributes = wm_get_block_styling($default_classes);
    $styling_attributes = wm_apply_auto_text_color($styling_attributes);

    $custom_attrs_array = [];
    if (!empty($custom_attributes)) {
        if (is_string($custom_attributes)) {
            $custom_attrs_array['custom'] = $custom_attributes;
        } elseif (is_array($custom_attributes)) {
            foreach ($custom_attributes as $key => $value) {
                if (is_numeric($key)) {
                    $custom_attrs_array["custom_{$key}"] = $value;
                } else {
                    $custom_attrs_array[$key] = $key . '="' . esc_attr($value) . '"';
                }
            }
        }
    }

    return wm_get_attributes_string(array_merge($styling_attributes, $custom_attrs_array));
}

/**
 * Apply automatic text color based on background color
 */
function wm_apply_auto_text_color($attributes): array {
    if (!isset($attributes['class'])) {
        return $attributes;
    }

    $class_string = $attributes['class'];

    // Check if text color is already set
    if (preg_match('/text-(white|black|gray|primary|secondary|dark|light)/', $class_string)) {
        return $attributes;
    }

    // Extract background color
    if (preg_match('/bg-([\w-]+)/', $class_string, $matches)) {
        $auto_text = wm_get_text_color_for_background('bg-' . $matches[1], ['prefer_white' => true]);
        if ($auto_text) {
            $attributes['class'] = str_replace('class="', 'class="' . $auto_text . ' ', $attributes['class']);
        }
    }

    return $attributes;
}
