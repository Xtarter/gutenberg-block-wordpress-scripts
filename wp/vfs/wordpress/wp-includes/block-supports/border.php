<?php
 function wp_register_border_support( $block_type ) { if ( ! $block_type->attributes ) { $block_type->attributes = array(); } if ( block_has_support( $block_type, array( '__experimentalBorder' ) ) && ! array_key_exists( 'style', $block_type->attributes ) ) { $block_type->attributes['style'] = array( 'type' => 'object', ); } if ( wp_has_border_feature_support( $block_type, 'color' ) && ! array_key_exists( 'borderColor', $block_type->attributes ) ) { $block_type->attributes['borderColor'] = array( 'type' => 'string', ); } } function wp_apply_border_support( $block_type, $block_attributes ) { if ( wp_should_skip_block_supports_serialization( $block_type, 'border' ) ) { return array(); } $border_block_styles = array(); $has_border_color_support = wp_has_border_feature_support( $block_type, 'color' ); $has_border_width_support = wp_has_border_feature_support( $block_type, 'width' ); if ( wp_has_border_feature_support( $block_type, 'radius' ) && isset( $block_attributes['style']['border']['radius'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'radius' ) ) { $border_radius = $block_attributes['style']['border']['radius']; if ( is_numeric( $border_radius ) ) { $border_radius .= 'px'; } $border_block_styles['radius'] = $border_radius; } if ( wp_has_border_feature_support( $block_type, 'style' ) && isset( $block_attributes['style']['border']['style'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'style' ) ) { $border_block_styles['style'] = $block_attributes['style']['border']['style']; } if ( $has_border_width_support && isset( $block_attributes['style']['border']['width'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'width' ) ) { $border_width = $block_attributes['style']['border']['width']; if ( is_numeric( $border_width ) ) { $border_width .= 'px'; } $border_block_styles['width'] = $border_width; } if ( $has_border_color_support && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'color' ) ) { $preset_border_color = array_key_exists( 'borderColor', $block_attributes ) ? "var:preset|color|{$block_attributes['borderColor']}" : null; $custom_border_color = _wp_array_get( $block_attributes, array( 'style', 'border', 'color' ), null ); $border_block_styles['color'] = $preset_border_color ? $preset_border_color : $custom_border_color; } if ( $has_border_color_support || $has_border_width_support ) { foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) { $border = _wp_array_get( $block_attributes, array( 'style', 'border', $side ), null ); $border_side_values = array( 'width' => isset( $border['width'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'width' ) ? $border['width'] : null, 'color' => isset( $border['color'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'color' ) ? $border['color'] : null, 'style' => isset( $border['style'] ) && ! wp_should_skip_block_supports_serialization( $block_type, '__experimentalBorder', 'style' ) ? $border['style'] : null, ); $border_block_styles[ $side ] = $border_side_values; } } $attributes = array(); $styles = wp_style_engine_get_styles( array( 'border' => $border_block_styles ) ); if ( ! empty( $styles['classnames'] ) ) { $attributes['class'] = $styles['classnames']; } if ( ! empty( $styles['css'] ) ) { $attributes['style'] = $styles['css']; } return $attributes; } function wp_has_border_feature_support( $block_type, $feature, $default_value = false ) { if ( property_exists( $block_type, 'supports' ) && ( true === _wp_array_get( $block_type->supports, array( '__experimentalBorder' ), $default_value ) ) ) { return true; } return block_has_support( $block_type, array( '__experimentalBorder', $feature ), $default_value ); } WP_Block_Supports::get_instance()->register( 'border', array( 'register_attribute' => 'wp_register_border_support', 'apply' => 'wp_apply_border_support', ) ); 