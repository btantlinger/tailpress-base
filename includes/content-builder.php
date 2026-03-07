<?php
/**
 * Content Builder - ACF Flexible Content with Auto-Loading Blocks
 *
 * Blocks are auto-loaded from includes/blocks/ directory.
 * Each block file returns an array with key, name, label, sub_fields.
 * Common styling fields (spacing, color, CSS ID/classes) are injected automatically.
 *
 * @package TailPress
 */

/**
 * Inject a Content tab at the beginning of block fields
 */
function wm_inject_common_fields($block_config) {
    $content_tab = [
        'key'       => 'field_content_tab_' . $block_config['key'],
        'label'     => 'Content',
        'name'      => '',
        'type'      => 'tab',
        'placement' => 'top',
    ];

    if (isset($block_config['sub_fields']) && is_array($block_config['sub_fields'])) {
        array_unshift($block_config['sub_fields'], $content_tab);
    } else {
        $block_config['sub_fields'] = [$content_tab];
    }

    return $block_config;
}

/**
 * Get vertical spacing choices for padding/margin fields
 */
function wm_get_y_spacing_choices() {
    return [
        ''   => 'None',
        '1'  => 'XS',
        '2'  => 'SM',
        '4'  => 'MD',
        '6'  => 'LG',
        '8'  => 'XL',
        '12' => 'XXL',
        '16' => '3XL',
        '20' => '4XL',
        '24' => '5XL',
        '28' => '6XL',
        '32' => '7XL',
    ];
}

function wm_get_padding_choices() {
    $choices = [];
    foreach (wm_get_y_spacing_choices() as $key => $label) {
        $choices["py-{$key}"] = $label;
    }
    return $choices;
}

function wm_get_margin_choices() {
    $choices = [];
    foreach (wm_get_y_spacing_choices() as $key => $label) {
        $choices["my-{$key}"] = $label;
    }
    return $choices;
}

/**
 * Get responsive spacing choices for breakpoint-specific fields
 */
function wm_get_responsive_spacing_choices($breakpoint = 'sm', $type = 'py') {
    $base_choices = wm_get_y_spacing_choices();
    $base_choices[''] = 'Inherit';

    $choices = [];
    foreach ($base_choices as $value => $label) {
        if ($value === '') {
            $choices[''] = $label;
        } else {
            $choices["{$breakpoint}:{$type}-{$value}"] = $label;
        }
    }

    return $choices;
}

/**
 * Get color name choices
 */
function wm_get_color_names() {
    return [
        ''          => 'Default/Transparent',
        'primary'   => 'Primary',
        'secondary' => 'Secondary',
        'tertiary'  => 'Tertiary',
        'light'     => 'Light',
        'dark'      => 'Dark',
        'white'     => 'White',
        'black'     => 'Black',
        'gray'      => 'Gray',
    ];
}

/**
 * Get color shade choices
 */
function wm_get_color_shades() {
    return [
        ''    => 'Base (500)',
        '50'  => '50',
        '100' => '100',
        '200' => '200',
        '300' => '300',
        '400' => '400',
        '500' => '500',
        '600' => '600',
        '700' => '700',
        '800' => '800',
        '900' => '900',
    ];
}

/**
 * Inject Styling tab with spacing, color, and CSS ID/classes fields
 */
function wm_inject_styling_fields($block_config) {
    $key = $block_config['key'];

    $styling_tab = [
        'key' => 'field_styling_tab_' . $key, 'label' => 'Styling',
        'name' => '', 'type' => 'tab', 'placement' => 'top',
    ];

    $css_id_field = [
        'key' => 'field_css_id_' . $key, 'label' => 'Custom CSS ID', 'name' => 'css_id',
        'type' => 'text', 'placeholder' => 'e.g. my-section-id',
        'wrapper' => ['width' => '50'], 'prepend' => 'id:', 'required' => 0, 'maxlength' => 100,
    ];

    $css_classes_field = [
        'key' => 'field_css_classes_' . $key, 'label' => 'Custom CSS Classes', 'name' => 'css_classes',
        'type' => 'text', 'placeholder' => 'e.g. custom-class another-class',
        'wrapper' => ['width' => '50'], 'prepend' => 'class:', 'required' => 0,
    ];

    $padding_y_field = [
        'key' => 'field_py_' . $key, 'label' => 'Padding Vertical', 'name' => 'padding_y',
        'type' => 'select', 'choices' => wm_get_padding_choices(),
        'wrapper' => ['width' => '50'], 'allow_null' => 1,
    ];

    $margin_y_field = [
        'key' => 'field_my_' . $key, 'label' => 'Margin Vertical', 'name' => 'margin_y',
        'type' => 'select', 'choices' => wm_get_margin_choices(),
        'wrapper' => ['width' => '50'], 'allow_null' => 1,
    ];

    // Responsive spacing accordion
    $responsive_acc_start = [
        'key' => 'field_acc_responsive_spacing_' . $key, 'label' => 'Responsive Spacing (Advanced)',
        'name' => '', 'type' => 'accordion', 'open' => 0, 'multi_expand' => 0, 'endpoint' => 0,
    ];

    $responsive_fields = [];
    $breakpoints = [
        'sm' => ['label' => 'SM+ 600px', 'key_suffix' => '_sm'],
        'md' => ['label' => 'MD+ 782px', 'key_suffix' => '_md'],
        'lg' => ['label' => 'LG+ 960px', 'key_suffix' => '_lg'],
        'xl' => ['label' => 'XL+ 1280px', 'key_suffix' => '_xl'],
    ];

    foreach ($breakpoints as $bp => $config) {
        $responsive_fields[] = [
            'key' => 'field_py_' . $bp . '_' . $key, 'label' => 'Padding Y (' . $config['label'] . ')',
            'name' => 'padding_y' . $config['key_suffix'], 'type' => 'select',
            'choices' => wm_get_responsive_spacing_choices($bp, 'py'),
            'wrapper' => ['width' => '50'], 'allow_null' => 1,
        ];
        $responsive_fields[] = [
            'key' => 'field_my_' . $bp . '_' . $key, 'label' => 'Margin Y (' . $config['label'] . ')',
            'name' => 'margin_y' . $config['key_suffix'], 'type' => 'select',
            'choices' => wm_get_responsive_spacing_choices($bp, 'my'),
            'wrapper' => ['width' => '50'], 'allow_null' => 1,
        ];
    }

    $responsive_acc_end = [
        'key' => 'field_acc_responsive_spacing_end_' . $key,
        'label' => '', 'name' => '', 'type' => 'accordion', 'endpoint' => 1,
    ];

    // Color fields
    $no_shade_condition = function ($field_key) {
        return [
            [
                ['field' => $field_key, 'operator' => '!=', 'value' => ''],
                ['field' => $field_key, 'operator' => '!=', 'value' => 'white'],
                ['field' => $field_key, 'operator' => '!=', 'value' => 'black'],
            ],
        ];
    };

    $bg_color_name_field = [
        'key' => 'field_bg_color_name_' . $key, 'label' => 'Background Color',
        'name' => 'bg_color_name', 'type' => 'select',
        'choices' => wm_get_color_names(), 'allow_null' => 1,
    ];

    $text_color_name_field = [
        'key' => 'field_text_color_name_' . $key, 'label' => 'Text Color',
        'name' => 'text_color_name', 'type' => 'select',
        'choices' => wm_get_color_names(), 'allow_null' => 1,
    ];

    $bg_color_shade_field = [
        'key' => 'field_bg_color_shade_' . $key, 'label' => 'Background Shade',
        'name' => 'bg_color_shade', 'type' => 'select',
        'choices' => wm_get_color_shades(), 'allow_null' => 1,
        'conditional_logic' => $no_shade_condition('field_bg_color_name_' . $key),
    ];

    $text_color_shade_field = [
        'key' => 'field_text_color_shade_' . $key, 'label' => 'Text Shade',
        'name' => 'text_color_shade', 'type' => 'select',
        'choices' => wm_get_color_shades(), 'allow_null' => 1,
        'conditional_logic' => $no_shade_condition('field_text_color_name_' . $key),
    ];

    if (isset($block_config['sub_fields']) && is_array($block_config['sub_fields'])) {
        $block_config['sub_fields'][] = $styling_tab;

        $spacing_group = [
            'key' => 'field_group_spacing_' . $key, 'label' => 'Spacing',
            'name' => 'spacing', 'type' => 'group',
            'wrapper' => ['width' => '50'], 'layout' => 'block',
            'sub_fields' => array_merge(
                [$padding_y_field, $margin_y_field, $responsive_acc_start],
                $responsive_fields,
                [$responsive_acc_end]
            ),
        ];

        $color_group = [
            'key' => 'field_group_color_' . $key, 'label' => 'Color',
            'name' => 'color', 'type' => 'group',
            'wrapper' => ['width' => '50'], 'layout' => 'block',
            'sub_fields' => [
                [
                    'key' => 'field_group_color_name_' . $key, 'name' => 'bg_color_group',
                    'type' => 'group', 'wrapper' => ['width' => '50'], 'layout' => 'block',
                    'sub_fields' => [$bg_color_name_field, $bg_color_shade_field],
                ],
                [
                    'key' => 'field_group_color_shade_' . $key, 'name' => 'text_color_group',
                    'type' => 'group', 'wrapper' => ['width' => '50'], 'layout' => 'block',
                    'sub_fields' => [$text_color_name_field, $text_color_shade_field],
                ],
            ],
        ];

        $block_config['sub_fields'][] = $css_id_field;
        $block_config['sub_fields'][] = $css_classes_field;
        $block_config['sub_fields'][] = $spacing_group;
        $block_config['sub_fields'][] = $color_group;
    }

    return $block_config;
}

/**
 * Auto-load block definitions and register the Content Builder field group
 */
if (function_exists('acf_add_local_field_group')) {

    function wm_load_flexible_content_blocks() {
        $blocks_dir = __DIR__ . '/blocks/';
        $layouts = [];

        if (!is_dir($blocks_dir)) {
            return $layouts;
        }

        $block_files = glob($blocks_dir . '*.php');

        foreach ($block_files as $block_file) {
            $block_name = basename($block_file, '.php');
            $block_config = require $block_file;

            if (is_array($block_config)) {
                $block_config = wm_inject_common_fields($block_config);
                $block_config = wm_inject_styling_fields($block_config);
                $layouts[$block_name] = $block_config;
            } else {
                error_log("Block file {$block_file} must return an array");
            }
        }

        return $layouts;
    }

    $block_layouts = wm_load_flexible_content_blocks();

    acf_add_local_field_group([
        'key'                   => 'group_content_builder',
        'title'                 => 'Content Builder',
        'instruction_placement' => 'field',
        'fields'                => [
            [
                'key'          => 'field_page_content',
                'label'        => 'Page Content',
                'name'         => 'page_content',
                'type'         => 'flexible_content',
                'instructions' => 'Add and arrange content sections for this page.',
                'layouts'      => $block_layouts,
                'button_label' => 'Add Content Block',
            ],
        ],
        'location'              => [
            [
                [
                    'param'    => 'page_template',
                    'operator' => '==',
                    'value'    => 'page-flexible.php',
                ],
            ],
        ],
        'menu_order'            => 1,
        'position'              => 'acf_after_title',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'hide_on_screen'        => ['the_content'],
        'active'                => 1,
    ]);
}
