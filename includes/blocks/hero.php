<?php
/**
 * Hero Block Definition (Example)
 *
 * This is an example block showing the pattern for creating new blocks.
 * Each block file in includes/blocks/ returns an array with:
 *   - key: unique layout key
 *   - name: layout name (maps to template-parts/blocks/{name}.php via underscore-to-hyphen)
 *   - label: display label in admin
 *   - display: 'block' layout display
 *   - sub_fields: array of ACF field definitions
 *
 * Styling fields (spacing, color, CSS ID/classes) are automatically injected.
 *
 * @package TailPress
 */

/**
 * Generate responsive hero height CSS
 */
function wm_get_hero_height_css($size = 'large') {
    $heights = [
        '2xl'    => ['mobile' => '450px', 'desktop' => '760px'],
        'xl'     => ['mobile' => '400px', 'desktop' => '600px'],
        'large'  => ['mobile' => '350px', 'desktop' => '500px'],
        'medium' => ['mobile' => '300px', 'desktop' => '400px'],
        'small'  => ['mobile' => '250px', 'desktop' => '320px'],
    ];

    $config = $heights[$size] ?? $heights['large'];

    return "
        height: {$config['mobile']};
        @media (min-width: 768px) {
            height: {$config['desktop']};
        }
    ";
}

return [
    'key'        => 'layout_hero',
    'name'       => 'hero',
    'label'      => 'Hero Section',
    'display'    => 'block',
    'sub_fields' => [
        [
            'key'           => 'field_hero_image',
            'label'         => 'Hero Background Image',
            'name'          => 'hero_image',
            'type'          => 'image',
            'instructions'  => 'Upload a high-resolution image (minimum 1920x800px).',
            'required'      => 1,
            'wrapper'       => ['width' => '50'],
            'return_format' => 'array',
            'preview_size'  => 'medium',
            'library'       => 'all',
        ],
        [
            'key'           => 'field_overlay_opacity',
            'label'         => 'Overlay Opacity (%)',
            'name'          => 'overlay_opacity',
            'type'          => 'range',
            'wrapper'       => ['width' => '50'],
            'default_value' => 40,
            'min'           => 0,
            'max'           => 100,
            'step'          => 5,
        ],
        [
            'key'         => 'field_hero_heading',
            'label'       => 'Main Heading',
            'name'        => 'hero_heading',
            'type'        => 'text',
            'required'    => 1,
            'wrapper'     => ['width' => '50'],
            'placeholder' => 'Your Main Heading',
            'maxlength'   => 120,
        ],
        [
            'key'         => 'field_hero_subheading',
            'label'       => 'Sub Heading',
            'name'        => 'hero_subheading',
            'type'        => 'textarea',
            'wrapper'     => ['width' => '50'],
            'placeholder' => 'Supporting text or description...',
            'maxlength'   => 300,
            'rows'        => 3,
            'new_lines'   => 'br',
        ],
        [
            'key'         => 'field_hero_cta_text',
            'label'       => 'Call to Action Text',
            'name'        => 'hero_cta_text',
            'type'        => 'text',
            'wrapper'     => ['width' => '50'],
            'placeholder' => 'Get Started',
            'maxlength'   => 50,
        ],
        [
            'key'               => 'field_hero_cta_url',
            'label'             => 'Call to Action URL',
            'name'              => 'hero_cta_url',
            'type'              => 'url',
            'wrapper'           => ['width' => '50'],
            'placeholder'       => 'https://example.com',
            'conditional_logic' => [
                [['field' => 'field_hero_cta_text', 'operator' => '!=empty']],
            ],
        ],
        [
            'key'           => 'field_hero_text_alignment',
            'label'         => 'Text Alignment',
            'name'          => 'hero_text_alignment',
            'type'          => 'select',
            'wrapper'       => ['width' => '33.33'],
            'choices'       => ['center' => 'Center', 'left' => 'Left', 'right' => 'Right'],
            'default_value' => 'center',
        ],
        [
            'key'           => 'field_hero_height',
            'label'         => 'Hero Height',
            'name'          => 'hero_height',
            'type'          => 'select',
            'wrapper'       => ['width' => '50'],
            'choices'       => [
                '2xl'    => '2X-Large (450px mobile, 760px desktop)',
                'xl'     => 'X-Large (400px mobile, 600px desktop)',
                'large'  => 'Large (350px mobile, 500px desktop)',
                'medium' => 'Medium (300px mobile, 400px desktop)',
                'small'  => 'Small (250px mobile, 320px desktop)',
            ],
            'default_value' => 'large',
        ],
    ],
];
