<?php
/**
 * Server-side registering and rendering of the `core/navigation-link` block.
 *
 * @package WordPress
 */

/**
 * Build an array with CSS classes and inline styles defining the colors
 * which will be applied to the navigation markup in the front-end.
 *
 * @param  array $context     Navigation block context.
 * @param  array $attributes  Block attributes.
 * @param  bool  $is_sub_menu Whether the link is part of a sub-menu.
 * @return array Colors CSS classes and inline styles.
 */
function block_core_navigation_link_build_css_colors( $context, $attributes, $is_sub_menu = false ) {
	$colors = array(
		'css_classes'   => array(),
		'inline_styles' => '',
	);

	// Text color.
	$named_text_color  = null;
	$custom_text_color = null;

	if ( $is_sub_menu && array_key_exists( 'customOverlayTextColor', $context ) ) {
		$custom_text_color = $context['customOverlayTextColor'];
	} elseif ( $is_sub_menu && array_key_exists( 'overlayTextColor', $context ) ) {
		$named_text_color = $context['overlayTextColor'];
	} elseif ( array_key_exists( 'customTextColor', $context ) ) {
		$custom_text_color = $context['customTextColor'];
	} elseif ( array_key_exists( 'textColor', $context ) ) {
		$named_text_color = $context['textColor'];
	} elseif ( isset( $context['style']['color']['text'] ) ) {
		$custom_text_color = $context['style']['color']['text'];
	}

	// If has text color.
	if ( ! is_null( $named_text_color ) ) {
		// Add the color class.
		array_push( $colors['css_classes'], 'has-text-color', sprintf( 'has-%s-color', $named_text_color ) );
	} elseif ( ! is_null( $custom_text_color ) ) {
		// Add the custom color inline style.
		$colors['css_classes'][]  = 'has-text-color';
		$colors['inline_styles'] .= sprintf( 'color: %s;', $custom_text_color );
	}

	// Background color.
	$named_background_color  = null;
	$custom_background_color = null;

	if ( $is_sub_menu && array_key_exists( 'customOverlayBackgroundColor', $context ) ) {
		$custom_background_color = $context['customOverlayBackgroundColor'];
	} elseif ( $is_sub_menu && array_key_exists( 'overlayBackgroundColor', $context ) ) {
		$named_background_color = $context['overlayBackgroundColor'];
	} elseif ( array_key_exists( 'customBackgroundColor', $context ) ) {
		$custom_background_color = $context['customBackgroundColor'];
	} elseif ( array_key_exists( 'backgroundColor', $context ) ) {
		$named_background_color = $context['backgroundColor'];
	} elseif ( isset( $context['style']['color']['background'] ) ) {
		$custom_background_color = $context['style']['color']['background'];
	}

	// If has background color.
	if ( ! is_null( $named_background_color ) ) {
		// Add the background-color class.
		array_push( $colors['css_classes'], 'has-background', sprintf( 'has-%s-background-color', $named_background_color ) );
	} elseif ( ! is_null( $custom_background_color ) ) {
		// Add the custom background-color inline style.
		$colors['css_classes'][]  = 'has-background';
		$colors['inline_styles'] .= sprintf( 'background-color: %s;', $custom_background_color );
	}

	return $colors;
}

/**
 * Build an array with CSS classes and inline styles defining the font sizes
 * which will be applied to the navigation markup in the front-end.
 *
 * @param  array $context Navigation block context.
 * @return array Font size CSS classes and inline styles.
 */
function block_core_navigation_link_build_css_font_sizes( $context ) {
	// CSS classes.
	$font_sizes = array(
		'css_classes'   => array(),
		'inline_styles' => '',
	);

	$has_named_font_size  = array_key_exists( 'fontSize', $context );
	$has_custom_font_size = isset( $context['style']['typography']['fontSize'] );

	if ( $has_named_font_size ) {
		// Add the font size class.
		$font_sizes['css_classes'][] = sprintf( 'has-%s-font-size', $context['fontSize'] );
	} elseif ( $has_custom_font_size ) {
		// Add the custom font size inline style.
		$font_sizes['inline_styles'] = sprintf(
			'font-size: %s;',
			wp_get_typography_font_size_value(
				array(
					'size' => $context['style']['typography']['fontSize'],
				)
			)
		);
	}

	return $font_sizes;
}

/**
 * Returns the top-level submenu SVG chevron icon.
 *
 * @return string
 */
function block_core_navigation_link_render_submenu_icon() {
	return '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>';
}

/**
 * Decodes a url if it's encoded, returning the same url if not.
 *
 * @param string $url The url to decode.
 *
 * @return string $url Returns the decoded url.
 */
function block_core_navigation_link_maybe_urldecode( $url ) {
	$is_url_encoded = false;
	$query          = parse_url( $url, PHP_URL_QUERY );
	$query_params   = wp_parse_args( $query );

	foreach ( $query_params as $query_param ) {
		$can_query_param_be_encoded = is_string( $query_param ) && ! empty( $query_param );
		if ( ! $can_query_param_be_encoded ) {
			continue;
		}
		if ( rawurldecode( $query_param ) !== $query_param ) {
			$is_url_encoded = true;
			break;
		}
	}

	if ( $is_url_encoded ) {
		return rawurldecode( $url );
	}

	return $url;
}


/**
 * Renders the `core/navigation-link` block.
 *
 * @param array    $attributes The block attributes.
 * @param string   $content    The saved content.
 * @param WP_Block $block      The parsed block.
 *
 * @return string Returns the post content with the legacy widget added.
 */
function render_block_core_navigation_link( $attributes, $content, $block ) {
	$navigation_link_has_id = isset( $attributes['id'] ) && is_numeric( $attributes['id'] );
	$is_post_type           = isset( $attributes['kind'] ) && 'post-type' === $attributes['kind'];
	$is_post_type           = $is_post_type || isset( $attributes['type'] ) && ( 'post' === $attributes['type'] || 'page' === $attributes['type'] );

	// Don't render the block's subtree if it is a draft or if the ID does not exist.
	if ( $is_post_type && $navigation_link_has_id ) {
		$post = get_post( $attributes['id'] );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return '';
		}
	}

	// Don't render the block's subtree if it has no label.
	if ( empty( $attributes['label'] ) ) {
		return '';
	}

	$font_sizes      = block_core_navigation_link_build_css_font_sizes( $block->context );
	$classes         = array_merge(
		$font_sizes['css_classes']
	);
	$style_attribute = $font_sizes['inline_styles'];

	$css_classes = trim( implode( ' ', $classes ) );
	$has_submenu = count( $block->inner_blocks ) > 0;
	$kind        = empty( $attributes['kind'] ) ? 'post_type' : str_replace( '-', '_', $attributes['kind'] );
	$is_active   = ! empty( $attributes['id'] ) && get_queried_object_id() === (int) $attributes['id'] && ! empty( get_queried_object()->$kind );

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => $css_classes . ' wp-block-navigation-item' . ( $has_submenu ? ' has-child' : '' ) .
				( $is_active ? ' current-menu-item' : '' ),
			'style' => $style_attribute,
		)
	);
	$html               = '<li ' . $wrapper_attributes . '>' .
		'<a class="wp-block-navigation-item__content" ';

	// Start appending HTML attributes to anchor tag.
	if ( isset( $attributes['url'] ) ) {
		$html .= ' href="' . esc_url( block_core_navigation_link_maybe_urldecode( $attributes['url'] ) ) . '"';
	}

	if ( $is_active ) {
		$html .= ' aria-current="page"';
	}

	if ( isset( $attributes['opensInNewTab'] ) && true === $attributes['opensInNewTab'] ) {
		$html .= ' target="_blank"  ';
	}

	if ( isset( $attributes['rel'] ) ) {
		$html .= ' rel="' . esc_attr( $attributes['rel'] ) . '"';
	} elseif ( isset( $attributes['nofollow'] ) && $attributes['nofollow'] ) {
		$html .= ' rel="nofollow"';
	}

	if ( isset( $attributes['title'] ) ) {
		$html .= ' title="' . esc_attr( $attributes['title'] ) . '"';
	}

	// End appending HTML attributes to anchor tag.

	// Start anchor tag content.
	$html .= '>' .
		// Wrap title with span to isolate it from submenu icon.
		'<span class="wp-block-navigation-item__label">';

	if ( isset( $attributes['label'] ) ) {
		$html .= wp_kses_post( $attributes['label'] );
	}

	$html .= '</span>';

	// Add description if available.
	if ( ! empty( $attributes['description'] ) ) {
		$html .= '<span class="wp-block-navigation-item__description">';
		$html .= wp_kses_post( $attributes['description'] );
		$html .= '</span>';
	}

	$html .= '</a>';
	// End anchor tag content.

	if ( isset( $block->context['showSubmenuIcon'] ) && $block->context['showSubmenuIcon'] && $has_submenu ) {
		// The submenu icon can be hidden by a CSS rule on the Navigation Block.
		$html .= '<span class="wp-block-navigation__submenu-icon">' . block_core_navigation_link_render_submenu_icon() . '</span>';
	}

	if ( $has_submenu ) {
		$inner_blocks_html = '';
		foreach ( $block->inner_blocks as $inner_block ) {
			$inner_blocks_html .= $inner_block->render();
		}

		$html .= sprintf(
			'<ul class="wp-block-navigation__submenu-container">%s</ul>',
			$inner_blocks_html
		);
	}

	$html .= '</li>';

	return $html;
}

/**
 * Registers a variation for a post type / taxonomy for the navigation link block.
 *
 * @since 6.5.0
 * @deprecated 6.5.0 Use WP_Block_Type::get_variations / get_block_type_variations filter instead.
 *
 * TODO: After two WP versions (6.7), we can remove this.
 *
 * @param array $variation Variation array from build_variation_for_navigation_link.
 */
function block_core_navigation_link_register_variation( $variation ) {
	_deprecated_function( __FUNCTION__, '6.5.0', 'WP_Block_Type::get_variations' );
	/*
	 * Directly set the variations on the registered block type
	 * because there's no server side registration for variations (see #47170).
	 */
	$navigation_block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/navigation-link' );
	/*
	 * If the block is not registered yet, bail early.
	 * Variation will be registered in register_block_core_navigation_link then.
	 */
	if ( ! $navigation_block_type ) {
		return;
	}

	$navigation_block_type->variations = array_merge(
		$navigation_block_type->variations,
		array( $variation )
	);
}

/**
 * Unregisters a variation for a post type / taxonomy for the navigation link block.
 *
 * @since 6.5.0
 * @deprecated 6.5.0 Use WP_Block_Type::get_variations / get_block_type_variations filter instead.
 *
 * TODO: After two WP versions (6.7), we can remove this.
 *
 * @param string $name Name of the post type / taxonomy (which was used as variation name).
 */
function block_core_navigation_link_unregister_variation( $name ) {
	_deprecated_function( __FUNCTION__, '6.5.0', 'WP_Block_Type::get_variations' );
	/*
	 * Directly get the variations from the registered block type
	 * because there's no server side (un)registration for variations (see #47170).
	 */
	$navigation_block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/navigation-link' );
	// If the block is not registered (yet), there's no need to remove a variation.
	if ( ! $navigation_block_type || empty( $navigation_block_type->variations ) ) {
		return;
	}
	$variations = $navigation_block_type->variations;
	// Search for the variation and remove it from the array.
	foreach ( $variations as $i => $variation ) {
		if ( $variation['name'] === $name ) {
			unset( $variations[ $i ] );
			break;
		}
	}
	// Reindex array after removing one variation.
	$navigation_block_type->variations = array_values( $variations );
}

/**
 * Returns a navigation link variation
 *
 * @param WP_Taxonomy|WP_Post_Type $entity post type or taxonomy entity.
 * @param string                   $kind string of value 'taxonomy' or 'post-type'.
 *
 * @return array
 */
function build_variation_for_navigation_link( $entity, $kind ) {
	$title       = '';
	$description = '';

	if ( property_exists( $entity->labels, 'item_link' ) ) {
		$title = $entity->labels->item_link;
	}
	if ( property_exists( $entity->labels, 'item_link_description' ) ) {
		$description = $entity->labels->item_link_description;
	}

	$variation = array(
		'name'        => $entity->name,
		'title'       => $title,
		'description' => $description,
		'attributes'  => array(
			'type' => $entity->name,
			'kind' => $kind,
		),
	);

	// Tweak some value for the variations.
	$variation_overrides = array(
		'post_tag'    => array(
			'name'       => 'tag',
			'attributes' => array(
				'type' => 'tag',
				'kind' => $kind,
			),
		),
		'post_format' => array(
			// The item_link and item_link_description for post formats is the
			// same as for tags, so need to be overridden.
			'title'       => __( 'Post Format Link' ),
			'description' => __( 'A link to a post format' ),
			'attributes'  => array(
				'type' => 'post_format',
				'kind' => $kind,
			),
		),
	);

	if ( array_key_exists( $entity->name, $variation_overrides ) ) {
		$variation = array_merge(
			$variation,
			$variation_overrides[ $entity->name ]
		);
	}

	return $variation;
}

/**
 * Filters the registered variations for a block type.
 * Returns the dynamically built variations for all post-types and taxonomies.
 *
 * @since 6.5.0
 *
 * @param array         $variations Array of registered variations for a block type.
 * @param WP_Block_Type $block_type The full block type object.
 */
function block_core_navigation_link_filter_variations( $variations, $block_type ) {
	if ( 'core/navigation-link' !== $block_type->name ) {
		return $variations;
	}

	$generated_variations = block_core_navigation_link_build_variations();
	// TODO: Mabe check if there are already variations for this post type/taxonomy?
	return array_merge( $variations, $generated_variations );
}

/**
 * Returns an array of variations for the navigation link block.
 *
 * @since 6.5.0
 *
 * @return array
 */
function block_core_navigation_link_build_variations() {
	// This will only handle post types and taxonomies registered until this point.
	$post_types = get_post_types( array( 'show_in_nav_menus' => true ), 'objects' );
	$taxonomies = get_taxonomies( array( 'show_in_nav_menus' => true ), 'objects' );

	/*
	 * Use two separate arrays as a way to order the variations in the UI.
	 * Known variations (like Post Link and Page Link) are added to the
	 * `built_ins` array. Variations for custom post types and taxonomies are
	 * added to the `variations` array and will always appear after `built-ins.
	 */
	$built_ins  = array();
	$variations = array();

	if ( $post_types ) {
		foreach ( $post_types as $post_type ) {
			$variation = build_variation_for_navigation_link( $post_type, 'post-type' );
			if ( $post_type->_builtin ) {
				$built_ins[] = $variation;
			} else {
				$variations[] = $variation;
			}
		}
	}
	if ( $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			$variation = build_variation_for_navigation_link( $taxonomy, 'taxonomy' );
			if ( $taxonomy->_builtin ) {
				$built_ins[] = $variation;
			} else {
				$variations[] = $variation;
			}
		}
	}

	return array_merge( $built_ins, $variations );
}

/**
 * Registers the navigation link block.
 *
 * @uses render_block_core_navigation_link()
 * @uses build_navigation_link_block_variations()
 * @throws WP_Error An WP_Error exception parsing the block definition.
 */
function register_block_core_navigation_link() {
	/*
	 * On Core versions < 6.5 the get_block_type_variations is not available.
	 * Therefore directly register the (until here) known variations.
	 * This keeps pre-6.5 behaviour in pre-6.5 versions.
	 *
	 * This callback will be called by a shim in lib/compat/wordpress-6.5/blocks.php
	 *
	 * TODO: After two WP versions (6.7), we can remove this.
	 */
	$variations_callback = null;
	if ( ! method_exists( 'WP_Block_Type', 'get_variations' ) ) {
		$variations_callback = 'build_navigation_link_block_variations';
	}
	register_block_type_from_metadata(
		__DIR__ . '/navigation-link',
		array(
			'render_callback'    => 'render_block_core_navigation_link',
			'variation_callback' => $variations_callback,
		)
	);
}
add_action( 'init', 'register_block_core_navigation_link' );
/**
 * Creates all variations for post types / taxonomies dynamically (= each time when variations are requested).
 * Do not use variation_callback, to also account for unregistering post types/taxonomies later on.
 */
add_action( 'get_block_type_variations', 'block_core_navigation_link_filter_variations', 10, 2 );

/**
 * Registers custom post type variations for navigation link on post type registration
 * Handles all post types registered after the block is registered in register_navigation_link_post_type_variations
 *
 * @since 6.5.0
 * @deprecated 6.5.0 Use WP_Block_Type::get_variations / get_block_type_variations filter instead.
 *
 * TODO: After two WP versions (6.7), we can remove this.
 *
 * @param string       $post_type The post type name passed from registered_post_type action hook.
 * @param WP_Post_Type $post_type_object The post type object passed from registered_post_type.
 */
function block_core_navigation_link_register_post_type_variation( $post_type, $post_type_object ) {
	_deprecated_function( __FUNCTION__, '6.5.0', 'WP_Block_Type::get_variations' );
	if ( $post_type_object->show_in_nav_menus ) {
		$variation = build_variation_for_navigation_link( $post_type_object, 'post-type' );
		block_core_navigation_link_register_variation( $variation );
	}
}

/**
 * Registers a custom taxonomy variation for navigation link on taxonomy registration
 * Handles all taxonomies registered after the block is registered in register_navigation_link_post_type_variations
 *
 * @since 6.5.0
 * @deprecated 6.5.0 Use WP_Block_Type::get_variations / get_block_type_variations filter instead.
 *
 * TODO: After two WP versions (6.7), we can remove this.
 *
 * @param string       $taxonomy Taxonomy slug.
 * @param array|string $object_type Object type or array of object types.
 * @param array        $args Array of taxonomy registration arguments.
 */
function block_core_navigation_link_register_taxonomy_variation( $taxonomy, $object_type, $args ) {
	_deprecated_function( __FUNCTION__, '6.5.0', 'WP_Block_Type::get_variations' );
	if ( isset( $args['show_in_nav_menus'] ) && $args['show_in_nav_menus'] ) {
		$variation = build_variation_for_navigation_link( (object) $args, 'post-type' );
		block_core_navigation_link_register_variation( $variation );
	}
}

/**
 * Unregisters a custom post type variation for navigation link on post type unregistration.
 *
 * @since 6.5.0
 * @deprecated 6.5.0 Use WP_Block_Type::get_variations / get_block_type_variations filter instead.
 *
 * TODO: After two WP versions (6.7), we can remove this.
 *
 * @param string $post_type The post type name passed from unregistered_post_type action hook.
 */
function block_core_navigation_link_unregister_post_type_variation( $post_type ) {
	_deprecated_function( __FUNCTION__, '6.5.0', 'WP_Block_Type::get_variations' );
	block_core_navigation_link_unregister_variation( $post_type );
}

/**
 * Unregisters a custom taxonomy variation for navigation link on taxonomy unregistration.
 *
 * @since 6.5.0
 * @deprecated 6.5.0 Use WP_Block_Type::get_variations / get_block_type_variations filter instead.
 *
 * TODO: After two WP versions (6.7), we can remove this.
 *
 * @param string $taxonomy The taxonomy name passed from unregistered_taxonomy action hook.
 */
function block_core_navigation_link_unregister_taxonomy_variation( $taxonomy ) {
	_deprecated_function( __FUNCTION__, '6.5.0', 'WP_Block_Type::get_variations' );
	block_core_navigation_link_unregister_variation( $taxonomy );
}
