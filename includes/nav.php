<?php
/**
 * Alpine Navigation Menu Walker
 *
 * Generates navigation markup compatible with the AlpineJS nav system
 * with support for multiple strategies per menu item.
 */
class Alpine_Nav_Walker extends Walker_Nav_Menu {

	private $default_strategy = 'nested-list';
	private $has_mobile_menu = true;
	private $max_depth = 5;

	public function __construct($options = [] ) {
		if (isset($options['default_strategy'])) {
			$this->default_strategy = $options['default_strategy'];
		}
		if (isset($options['has_mobile_menu'])) {
			$this->has_mobile_menu = $options['has_mobile_menu'];
		}
		if (isset($options['max_depth'])) {
			$this->max_depth = $options['max_depth'];
		}
	}

	/**
	 * Starts the list before the elements are added.
	 */
	public function start_lvl(&$output, $depth = 0, $args = null) {
		$indent = str_repeat("\t", $depth);
		$level_class = 'wm-nav__list--level-' . ($depth + 1);
		$output .= "\n$indent<ul class=\"wm-nav__list $level_class\" data-level=\"" . ($depth + 1) . "\">\n";
	}

	/**
	 * Ends the list after the elements are added.
	 */
	public function end_lvl(&$output, $depth = 0, $args = null) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * Starts the element output.
	 */
	public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
		$indent = ($depth) ? str_repeat("\t", $depth) : '';

		$classes = empty($item->classes) ? [] : (array) $item->classes;
		$classes[] = 'wm-nav__item';
		$classes[] = 'wm-nav__item--level-' . $depth;
		$classes[] = 'menu-item-' . $item->ID;

		// Check if item has children
		$has_children = in_array('menu-item-has-children', $classes);
		if ($has_children) {
			$classes[] = 'wm-nav__item--parent';
		}

		// Get strategy for root level items only, but we need to know parent strategy for nested items
		$strategy = '';
		$strategy_config = [];
		$anchor_element_id = '';
		$parent_strategy = '';

		// For nested items, find parent's strategy
		if ($depth > 0) {
			$parent_item = $item;
			$current_depth = $depth;
			while ($current_depth > 0 && $parent_item->menu_item_parent) {
				$parent_item = wp_setup_nav_menu_item(get_post($parent_item->menu_item_parent));
				$current_depth--;
				if ($current_depth === 0) {
					$parent_strategy = get_field('nav_strategy', $parent_item->ID);
					break;
				}
			}
		}

		if ($depth === 0) {
			$nav_strategy = get_field('nav_strategy', $item->ID);
			if ($nav_strategy) {
				$strategy = $nav_strategy;

				// Get additional config for panel strategies
				if (in_array($strategy, ['drop-down-panel', 'drop-down-fly-out-panel', 'content-panel'])) {
					$menu_width = get_field('nav_menu_width', $item->ID);
					$anchor_element_id = get_field('nav_anchor_element_id', $item->ID);
					$match_width_of_anchor = get_field('nav_match_width_of_anchor', $item->ID);
					$min_tile_width = get_field('nav_min_tile_width', $item->ID);
					$vertical_menu_width = get_field('nav_vertical_menu_width', $item->ID);

					// Content panel specific config
					if ($strategy === 'content-panel') {
						$content_layout = get_field('nav_content_layout', $item->ID);
						$enable_descriptions = get_field('nav_enable_descriptions', $item->ID);
						$content_blocks = get_field('nav_content_blocks', $item->ID);

						if ($content_layout) {
							$strategy_config['contentLayout'] = $content_layout;
						}
						if ($enable_descriptions) {
							$strategy_config['enableDescriptions'] = true;
						}
						// Note: contentBlocks are rendered as HTML, not passed to JS
						// This avoids JSON parsing issues with embedded x-data attributes
						if ($content_blocks) {
							$strategy_config['hasContentBlocks'] = true;
						}
					}

					// Add width class if set (only if not matching anchor width)
					if ($menu_width && !$match_width_of_anchor) {
						$classes[] = 'wm-nav__item--' . $menu_width;
					}

					// Store anchor element ID for positioning and optional width matching
					if ($anchor_element_id) {
						$strategy_config['anchorElementId'] = $anchor_element_id;

						// Store whether to match width of anchor element
						if ($match_width_of_anchor) {
							$strategy_config['matchWidthOfAnchor'] = true;
						}
					}

					// Store minimum tile width for responsive grid - pass as string to support any CSS unit
					if ($min_tile_width) {
						$strategy_config['minTileWidth'] = $min_tile_width; // Pass as-is, don't convert to int
					}

					// Store vertical menu width for flyout strategy
					if ($vertical_menu_width && $strategy === 'drop-down-fly-out-panel') {
						$strategy_config['verticalMenuWidth'] = ($vertical_menu_width);
					}
				}
			}
		}

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
		$id = $id ? ' id="' . esc_attr($id) . '"' : '';

		// Build Alpine.js x-data configuration
		$alpine_config = [
			'level' => $depth
		];

		if ($strategy) {
			$alpine_config['strategy'] = $strategy;
			$alpine_config = array_merge($alpine_config, $strategy_config);
		}

		$alpine_data = 'navItem(' . json_encode($alpine_config) . ')';

		// Build Alpine.js event handlers
		$alpine_events = '';
		if ($has_children) {
			$alpine_events = ' @mouseenter="openSubmenu($event)"';
			$alpine_events .= ' @mouseleave="closeSubmenu($event)"';
			$alpine_events .= ' @click.away="closeSubmenu($event)"';
		}

		// Add strategy data attribute if specified
		$data_strategy = $strategy ? ' data-strategy="' . esc_attr($strategy) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names;
		$output .= ' x-data="' . esc_attr($alpine_data) . '"';
		$output .= $alpine_events;
		$output .= ' :class="classes()"';
		// Don't apply styles() here - it's for the li element
		$output .= $data_strategy;
		$output .= '>';

		// Build the link
		$attributes = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) .'"' : '';
		$attributes .= ! empty($item->target)     ? ' target="' . esc_attr($item->target) .'"' : '';
		$attributes .= ! empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn) .'"' : '';
		$attributes .= ! empty($item->url)        ? ' href="'   . esc_attr($item->url) .'"' : '';

		// Add aria-expanded for accessibility
		if ($has_children) {
			$attributes .= ' x-bind:aria-expanded="desktopOpen ? \'true\' : \'false\'"';
		}

		$item_output = isset($args->before) ? $args->before : '';
		$item_output .= '<a' . $attributes . ' class="wm-nav__link" x-ref="trigger">';

		// Add label span with data-label for mobile menu
		$label = apply_filters('the_title', $item->title, $item->ID);

		// Check if we need to add description support for content-panel strategy
		$item_description = '';
		$parent_enables_descriptions = false;

		if ($depth === 1) {
			// Get parent item to check if it enables descriptions
			$parent_item = wp_setup_nav_menu_item(get_post($item->menu_item_parent));
			$parent_strategy = get_field('nav_strategy', $parent_item->ID);
			$parent_enables_descriptions = ($parent_strategy === 'content-panel') && get_field('nav_enable_descriptions', $parent_item->ID);

			if ($parent_enables_descriptions) {
				$item_description = get_field('nav_item_description', $item->ID);
			}
		}

		if ($parent_enables_descriptions && $item_description) {
			// Use wrapper for label + description
			$item_output .= '<span class="wm-nav__label-wrapper">';
			$item_output .= '<span class="wm-nav__label" data-label="' . esc_attr($label) . '">';
			$item_output .= (isset($args->link_before) ? $args->link_before : '') . $label . (isset($args->link_after) ? $args->link_after : '');
			$item_output .= '</span>';
			$item_output .= '<span class="wm-nav__description">' . esc_html($item_description) . '</span>';
			$item_output .= '</span>';
		} else {
			// Standard label without description
			$item_output .= '<span class="wm-nav__label" data-label="' . esc_attr($label) . '">';
			$item_output .= (isset($args->link_before) ? $args->link_before : '') . $label . (isset($args->link_after) ? $args->link_after : '');
			$item_output .= '</span>';
		}

		// Add indicator for items with children
		if ($has_children) {
			$item_output .= '<span class="wm-nav__indicator"></span>';
		}

		$item_output .= '</a>';
		$item_output .= isset($args->after) ? $args->after : '';

		// Start submenu container if has children
		if ($has_children) {
			$submenu_id = 'nav-' . sanitize_title($item->title);

			// Determine anchor placement based on depth
			$anchor_placement = $depth === 0 ? 'bottom' : 'right-start';

			$item_output .= '<div class="wm-nav__submenu"';

			// Determine anchor reference
			// Special case: For flyout strategy level-1, don't use Alpine Anchor (we position manually)
			$skip_anchor = ($depth === 1 && $parent_strategy === 'drop-down-fly-out-panel');

			if (!$skip_anchor) {
				// Always anchor to the trigger element for positioning.
				// anchorElementId is only used for width matching (handled in JS via matchWidthOfAnchor).
				$item_output .= ' x-anchor.' . $anchor_placement . '.offset.8="$refs.trigger"';
			}

			$item_output .= ' x-show="shouldShowSubmenu()"';
			$item_output .= ' x-transition:enter="transition ease-out duration-200"';
			$item_output .= ' x-transition:enter-start="opacity-0 scale-95"';
			$item_output .= ' x-transition:enter-end="opacity-100 scale-100"';
			$item_output .= ' x-transition:leave="transition ease-in duration-150"';
			$item_output .= ' x-transition:leave-start="opacity-100 scale-100"';
			$item_output .= ' x-transition:leave-end="opacity-0 scale-95"';
			$item_output .= ' x-cloak';
			$item_output .= ' :class="submenuClasses()"';
			$item_output .= ' :style="submenuStyles()"'; // This applies the --min-tile-width CSS variable
			$item_output .= ' role="menu"';
			$item_output .= ' aria-orientation="vertical"';
			$item_output .= ' aria-labelledby="' . esc_attr($submenu_id) . '"';
			$item_output .= '>';

			// Add content panel wrapper for content-panel strategy
			if ($depth === 0 && $strategy === 'content-panel') {
				$content_layout = get_field('nav_content_layout', $item->ID) ?: 'content-below';
				$content_blocks = get_field('nav_content_blocks', $item->ID);

				$item_output .= '<div class="wm-nav__content-panel-wrapper">';

				// Render content and navigation in correct order based on layout
				if (in_array($content_layout, ['content-above', 'content-left', 'content-only'])) {
					// Content first (or content only)
					if (!empty($content_blocks)) {
						$item_output .= $this->render_content_blocks($content_blocks);
					}
					// Always add navigation section wrapper so CSS can hide it for content-only
					$item_output .= '<div class="wm-nav__navigation-section">';
				} else {
					// Navigation first, then content (for content-below, content-right, mixed)
					$item_output .= '<div class="wm-nav__navigation-section">';
					// Content will be added in end_el() method for these layouts
				}
			}
		}

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}

	/**
	 * Ends the element output.
	 */
	public function end_el(&$output, $item, $depth = 0, $args = null) {
		$has_children = in_array('menu-item-has-children', (array) $item->classes);

		// Close submenu container if has children
		if ($has_children) {
			// Close content panel wrappers for content-panel strategy
			if ($depth === 0) {
				$strategy = get_field('nav_strategy', $item->ID);
				if ($strategy === 'content-panel') {
					$content_layout = get_field('nav_content_layout', $item->ID) ?: 'content-below';
					$content_blocks = get_field('nav_content_blocks', $item->ID);

					// Close navigation section (always opened now, CSS hides for content-only)
					$output .= '</div>'; // Close navigation section

					// Add content blocks for layouts where content comes after navigation
					if (in_array($content_layout, ['content-below', 'content-right', 'mixed']) && !empty($content_blocks)) {
						$output .= $this->render_content_blocks($content_blocks);
					}

					$output .= '</div>'; // Close content panel wrapper
				}
			}

			$output .= '</div>'; // Close submenu
		}

		$output .= "</li>\n";
	}

	/**
	 * Render content blocks for content-panel strategy
	 */
	private function render_content_blocks($content_blocks) {
		if (empty($content_blocks)) {
			return '';
		}

		$output = '<div class="wm-nav__content-blocks">';

		foreach ($content_blocks as $block) {
			$layout = $block['acf_fc_layout'];
			$output .= '<div class="wm-nav__content-block wm-nav__content-block--' . esc_attr($layout) . '">';

			switch ($layout) {
				case 'text_block':
					if (!empty($block['title'])) {
						$output .= '<h3>' . esc_html($block['title']) . '</h3>';
					}
					if (!empty($block['content'])) {
						// Don't use wp_kses_post as it strips <style> tags - shortcode content is trusted
						$output .= '<div class="wm-nav__content-text">' . do_shortcode($block['content']) . '</div>';
					}
					break;

				case 'image_block':
					if (!empty($block['image'])) {
						$image = $block['image'];
						$link = !empty($block['link']) ? $block['link'] : '';

						if ($link) {
							$output .= '<a href="' . esc_url($link) . '" class="wm-nav__content-block--image-linked">';
						}

						$output .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($image['alt']) . '" />';

						if ($link) {
							$output .= '</a>';
						}
					}
					break;

				case 'cta_block':
					if (!empty($block['title'])) {
						$output .= '<h3>' . esc_html($block['title']) . '</h3>';
					}
					if (!empty($block['description'])) {
						$output .= '<p class="wm-nav__cta-description">' . esc_html($block['description']) . '</p>';
					}
					if (!empty($block['button_link']) && !empty($block['button_text'])) {
						$output .= '<a href="' . esc_url($block['button_link']) . '" class="wm-nav__cta-button">' . esc_html($block['button_text']) . '</a>';
					}
					break;
			}

			$output .= '</div>';
		}

		$output .= '</div>';
		return $output;
	}
}

/**
 * Helper function to display Alpine navigation
 */
function display_alpine_navigation($args = [] ) {
	$defaults = array(
		'theme_location' => 'primary',
		'menu_class' => 'wm-nav__list wm-nav__list--level-0',
		'container' => false,
		'walker' => new Alpine_Nav_Walker(),
		'fallback_cb' => false,
		'items_wrap' => '<ul id="%1$s" class="%2$s" data-nav-root data-level="0">%3$s</ul>',
	);

	$args = wp_parse_args($args, $defaults);

	wp_nav_menu($args);
}

/**
 * Shortcode for Alpine navigation
 */
function alpine_nav_shortcode($atts) {
	$atts = shortcode_atts( [
		'location' => 'primary',
		'strategy' => 'nested-list',
		'mobile' => 'true',
		'max_depth' => 5,
	], $atts);

	ob_start();

	$walker_options = [
		'default_strategy' => $atts['strategy'],
		'has_mobile_menu' => $atts['mobile'] === 'true',
		'max_depth' => intval($atts['max_depth']),
	];

	$menu_args = [
		'theme_location' => $atts['location'],
		'walker' => new Alpine_Nav_Walker($walker_options),
	];

	display_alpine_navigation($menu_args);

	return ob_get_clean();
}
add_shortcode('alpine_nav', 'alpine_nav_shortcode');



function wm_register_mega_menu_fields() {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	acf_add_local_field_group(array(
		'key' => 'group_mega_menu_column',
		'title' => 'Mega Menu Settings',
		'fields' => array(
			array(
				'key' => 'field_enable_mega_menu',
				'label' => 'Enable Mega Menu',
				'name' => 'enable_mega_menu',
				'type' => 'true_false',
				'default_value' => 0,
				'ui' => 1,
			),
			array(
				'key' => 'field_mega_menu_type',
				'label' => 'Mega Menu Type',
				'name' => 'mega_menu_type',
				'type' => 'select',
				'choices' => array(
					'column' => 'Column Menu',
				),
				'default_value' => 'column',
				'conditional_logic' => array(
					array(
						array(
							'field' => 'field_enable_mega_menu',
							'operator' => '==',
							'value' => '1',
						),
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'nav_menu_item',
					'operator' => '==',
					'value' => 'location/primary',
				),
			),
		),
	));
}
//add_action('acf/init', 'wm_register_mega_menu_fields' );



function add_nav_strategy_fields() {
	if (function_exists('acf_add_local_field_group')) {
		acf_add_local_field_group( [
			'key' => 'group_nav_strategy',
			'title' => 'Navigation Strategy',
			'fields' => [
				[
					'key' => 'field_nav_strategy',
					'label' => 'Navigation Strategy',
					'name' => 'nav_strategy',
					'type' => 'select',
					'instructions' => 'Select the navigation strategy for this top-level menu item. This only applies to root level menu items.',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'choices' => [
						'' => 'Use Default',
						'drill-down' => 'Drill Down',
						'drop-down-panel' => 'Drop Down Panel',
						'drop-down-fly-out-panel' => 'Drop Down Fly Out Panel',
						'content-panel' => 'Content Panel',
					],
					'default_value' => [],
					'allow_null' => 0,
					'multiple' => 0,
					'ui' => 0,
					'return_format' => 'value',
					'ajax' => 0,
					'placeholder' => '',
				],
				[
					'key' => 'field_nav_menu_width',
					'label' => 'Menu Width',
					'name' => 'nav_menu_width',
					'type' => 'select',
					'instructions' => 'Choose how wide this dropdown menu should be. (Disabled if "Match Width of Anchor" is enabled below)',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-panel',
							],
							[
								'field' => 'field_nav_match_width_of_anchor',
								'operator' => '!=',
								'value' => '1',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-fly-out-panel',
							],
							[
								'field' => 'field_nav_match_width_of_anchor',
								'operator' => '!=',
								'value' => '1',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
							[
								'field' => 'field_nav_match_width_of_anchor',
								'operator' => '!=',
								'value' => '1',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'choices' => [
						'' => 'Default',
						'width-small' => 'Small',
						'width-large' => 'Large', 
						'width-xl' => 'Extra Large',
					],
					'default_value' => '',
					'allow_null' => 1,
					'multiple' => 0,
					'ui' => 1,
					'return_format' => 'value',
					'ajax' => 0,
				],
				[
					'key' => 'field_nav_anchor_element_id',
					'label' => 'Anchor Element',
					'name' => 'nav_anchor_element_id',
					'type' => 'text',
					'instructions' => 'Enter a CSS selector (e.g., "#main-container" or ".site-wrapper") to anchor and optionally match width to a specific element. The menu will be positioned relative to this element.',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-panel',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-fly-out-panel',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'default_value' => '',
					'placeholder' => '#main-container',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				],
				[
					'key' => 'field_nav_match_width_of_anchor',
					'label' => 'Match Width of Anchor',
					'name' => 'nav_match_width_of_anchor',
					'type' => 'true_false',
					'instructions' => 'Enable to match the menu width to the anchor element above. The menu will automatically resize when the anchor element resizes. This overrides the fixed width setting.',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-panel',
							],
							[
								'field' => 'field_nav_anchor_element_id',
								'operator' => '!=empty',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-fly-out-panel',
							],
							[
								'field' => 'field_nav_anchor_element_id',
								'operator' => '!=empty',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
							[
								'field' => 'field_nav_anchor_element_id',
								'operator' => '!=empty',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'message' => '',
					'default_value' => 0,
					'ui' => 1,
					'ui_on_text' => 'Yes',
					'ui_off_text' => 'No',
				],
				[
					'key' => 'field_nav_min_tile_width',
					'label' => 'Minimum Tile Width',
					'name' => 'nav_min_tile_width',
					'type' => 'text',
					'instructions' => 'Set the minimum width for each tile. The grid will automatically create as many columns as fit. Supports any CSS unit (px, rem, %, etc). Examples: 200px (balanced), 15rem (relative), 48% (2 columns).',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-panel',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-fly-out-panel',
							],
						],
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'default_value' => '200px',
					'placeholder' => '200px, 15rem, 20%, etc.',
					'prepend' => '',
					'append' => '',
					'maxlength' => '',
				],
				[
					'key' => 'field_nav_vertical_menu_width',
					'label' => 'Vertical Menu Width',
					'name' => 'nav_vertical_menu_width',
					'type' => 'number',
					'instructions' => 'Set the width of the vertical menu (level-0 dropdown) in pixels. The flyout panel will appear to the right when hovering over menu items.',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'drop-down-fly-out-panel',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'default_value' => 300,
					'placeholder' => '300',
					'prepend' => '',
					'append' => 'px',
					'min' => 200,
					'max' => 500,
					'step' => 10,
				],
				[
					'key' => 'field_nav_content_layout',
					'label' => 'Content Layout',
					'name' => 'nav_content_layout',
					'type' => 'select',
					'instructions' => 'Choose how content blocks should be positioned relative to navigation links. "Mixed" creates a responsive grid where content and navigation flow together as equal sections.',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'choices' => [
						'content-below' => 'Content Below Navigation',
						'content-above' => 'Content Above Navigation',
						'content-left' => 'Content Left, Navigation Right',
						'content-right' => 'Navigation Left, Content Right',
						'mixed' => 'Mixed Grid Layout (Content & Navigation as Equal Sections)',
						'content-only' => 'Content Only (Hide Navigation)',
					],
					'default_value' => 'content-below',
					'allow_null' => 0,
					'multiple' => 0,
					'ui' => 1,
					'return_format' => 'value',
					'ajax' => 0,
				],
				[
					'key' => 'field_nav_enable_descriptions',
					'label' => 'Enable Link Descriptions',
					'name' => 'nav_enable_descriptions',
					'type' => 'true_false',
					'instructions' => 'Enable description fields for level-1 menu items. Descriptions will appear under the link title within each navigation block.',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'message' => '',
					'default_value' => 0,
					'ui' => 1,
					'ui_on_text' => 'Yes',
					'ui_off_text' => 'No',
				],
				[
					'key' => 'field_nav_content_blocks',
					'label' => 'Content Blocks',
					'name' => 'nav_content_blocks',
					'type' => 'flexible_content',
					'instructions' => 'Add content blocks that will be displayed in the content panel alongside or instead of navigation links.',
					'required' => 0,
					'conditional_logic' => [
						[
							[
								'field' => 'field_nav_strategy',
								'operator' => '==',
								'value' => 'content-panel',
							],
						],
					],
					'wrapper' => [
						'width' => '',
						'class' => '',
						'id' => '',
					],
					'layouts' => [
						'layout_text_block' => [
							'key' => 'layout_text_block',
							'name' => 'text_block',
							'label' => 'Text Block',
							'display' => 'block',
							'sub_fields' => [
								[
									'key' => 'field_text_block_title',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'default_value' => '',
									'placeholder' => 'Block title',
								],
								[
									'key' => 'field_text_block_content',
									'label' => 'Content',
									'name' => 'content',
									'type' => 'wysiwyg',
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 1,
									'delay' => 1,
								],
							],
						],
						'layout_image_block' => [
							'key' => 'layout_image_block',
							'name' => 'image_block',
							'label' => 'Image Block',
							'display' => 'block',
							'sub_fields' => [
								[
									'key' => 'field_image_block_image',
									'label' => 'Image',
									'name' => 'image',
									'type' => 'image',
									'return_format' => 'array',
									'preview_size' => 'medium',
									'library' => 'all',
								],
								[
									'key' => 'field_image_block_link',
									'label' => 'Link URL (Optional)',
									'name' => 'link',
									'type' => 'url',
									'placeholder' => 'https://example.com',
								],
							],
						],
						'layout_cta_block' => [
							'key' => 'layout_cta_block',
							'name' => 'cta_block',
							'label' => 'Call to Action Block',
							'display' => 'block',
							'sub_fields' => [
								[
									'key' => 'field_cta_block_title',
									'label' => 'Title',
									'name' => 'title',
									'type' => 'text',
									'default_value' => '',
									'placeholder' => 'CTA title',
								],
								[
									'key' => 'field_cta_block_description',
									'label' => 'Description',
									'name' => 'description',
									'type' => 'textarea',
									'rows' => 3,
									'placeholder' => 'Brief description',
								],
								[
									'key' => 'field_cta_block_button_text',
									'label' => 'Button Text',
									'name' => 'button_text',
									'type' => 'text',
									'default_value' => 'Learn More',
								],
								[
									'key' => 'field_cta_block_button_link',
									'label' => 'Button Link',
									'name' => 'button_link',
									'type' => 'url',
									'required' => 1,
								],
							],
						],
					],
					'button_label' => 'Add Content Block',
					'min' => 0,
					'max' => 10,
				],
				/* Layout types removed for now - too complex to implement properly */
			],
			'location' => [
				[
					[
						'param' => 'nav_menu_item',
						'operator' => '==',
						'value' => 'location/primary',
					],

				],
			],
			'menu_order' => 0,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
		] );
	}
}
add_action('acf/init', 'add_nav_strategy_fields');

function add_nav_item_description_field() {
	if (function_exists('acf_add_local_field_group')) {
		acf_add_local_field_group( [
			'key' => 'group_nav_item_description',
			'title' => 'Navigation Item Description',
			'fields' => [
				[
					'key' => 'field_nav_item_description',
					'label' => 'Description',
					'name' => 'nav_item_description',
					'type' => 'textarea',
					'instructions' => 'Add a description for this menu item. This will be displayed under the link title when the parent uses content-panel strategy with descriptions enabled.',
					'required' => 0,
					'rows' => 2,
					'placeholder' => 'Brief description of this menu item...',
				],
			],
			'location' => [
				[
					[
						'param' => 'nav_menu_item',
						'operator' => '==',
						'value' => 'location/primary',
					],
				],
			],
			'menu_order' => 1,
			'position' => 'normal',
			'style' => 'default',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
		] );
	}
}
add_action('acf/init', 'add_nav_item_description_field');

