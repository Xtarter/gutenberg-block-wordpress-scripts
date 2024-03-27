<?php
 function wp_register_typography_support( $block_type ) { if ( ! property_exists( $block_type, 'supports' ) ) { return; } $typography_supports = _wp_array_get( $block_type->supports, array( 'typography' ), false ); if ( ! $typography_supports ) { return; } $has_font_family_support = _wp_array_get( $typography_supports, array( '__experimentalFontFamily' ), false ); $has_font_size_support = _wp_array_get( $typography_supports, array( 'fontSize' ), false ); $has_font_style_support = _wp_array_get( $typography_supports, array( '__experimentalFontStyle' ), false ); $has_font_weight_support = _wp_array_get( $typography_supports, array( '__experimentalFontWeight' ), false ); $has_letter_spacing_support = _wp_array_get( $typography_supports, array( '__experimentalLetterSpacing' ), false ); $has_line_height_support = _wp_array_get( $typography_supports, array( 'lineHeight' ), false ); $has_text_decoration_support = _wp_array_get( $typography_supports, array( '__experimentalTextDecoration' ), false ); $has_text_transform_support = _wp_array_get( $typography_supports, array( '__experimentalTextTransform' ), false ); $has_typography_support = $has_font_family_support || $has_font_size_support || $has_font_style_support || $has_font_weight_support || $has_letter_spacing_support || $has_line_height_support || $has_text_decoration_support || $has_text_transform_support; if ( ! $block_type->attributes ) { $block_type->attributes = array(); } if ( $has_typography_support && ! array_key_exists( 'style', $block_type->attributes ) ) { $block_type->attributes['style'] = array( 'type' => 'object', ); } if ( $has_font_size_support && ! array_key_exists( 'fontSize', $block_type->attributes ) ) { $block_type->attributes['fontSize'] = array( 'type' => 'string', ); } if ( $has_font_family_support && ! array_key_exists( 'fontFamily', $block_type->attributes ) ) { $block_type->attributes['fontFamily'] = array( 'type' => 'string', ); } } function wp_apply_typography_support( $block_type, $block_attributes ) { if ( ! property_exists( $block_type, 'supports' ) ) { return array(); } $typography_supports = _wp_array_get( $block_type->supports, array( 'typography' ), false ); if ( ! $typography_supports ) { return array(); } if ( wp_should_skip_block_supports_serialization( $block_type, 'typography' ) ) { return array(); } $has_font_family_support = _wp_array_get( $typography_supports, array( '__experimentalFontFamily' ), false ); $has_font_size_support = _wp_array_get( $typography_supports, array( 'fontSize' ), false ); $has_font_style_support = _wp_array_get( $typography_supports, array( '__experimentalFontStyle' ), false ); $has_font_weight_support = _wp_array_get( $typography_supports, array( '__experimentalFontWeight' ), false ); $has_letter_spacing_support = _wp_array_get( $typography_supports, array( '__experimentalLetterSpacing' ), false ); $has_line_height_support = _wp_array_get( $typography_supports, array( 'lineHeight' ), false ); $has_text_decoration_support = _wp_array_get( $typography_supports, array( '__experimentalTextDecoration' ), false ); $has_text_transform_support = _wp_array_get( $typography_supports, array( '__experimentalTextTransform' ), false ); $should_skip_font_size = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontSize' ); $should_skip_font_family = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontFamily' ); $should_skip_font_style = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontStyle' ); $should_skip_font_weight = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'fontWeight' ); $should_skip_line_height = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'lineHeight' ); $should_skip_text_decoration = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'textDecoration' ); $should_skip_text_transform = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'textTransform' ); $should_skip_letter_spacing = wp_should_skip_block_supports_serialization( $block_type, 'typography', 'letterSpacing' ); $typography_block_styles = array(); if ( $has_font_size_support && ! $should_skip_font_size ) { $preset_font_size = array_key_exists( 'fontSize', $block_attributes ) ? "var:preset|font-size|{$block_attributes['fontSize']}" : null; $custom_font_size = isset( $block_attributes['style']['typography']['fontSize'] ) ? $block_attributes['style']['typography']['fontSize'] : null; $typography_block_styles['fontSize'] = $preset_font_size ? $preset_font_size : wp_get_typography_font_size_value( array( 'size' => $custom_font_size, ) ); } if ( $has_font_family_support && ! $should_skip_font_family ) { $preset_font_family = array_key_exists( 'fontFamily', $block_attributes ) ? "var:preset|font-family|{$block_attributes['fontFamily']}" : null; $custom_font_family = isset( $block_attributes['style']['typography']['fontFamily'] ) ? wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['fontFamily'], 'font-family' ) : null; $typography_block_styles['fontFamily'] = $preset_font_family ? $preset_font_family : $custom_font_family; } if ( $has_font_style_support && ! $should_skip_font_style && isset( $block_attributes['style']['typography']['fontStyle'] ) ) { $typography_block_styles['fontStyle'] = wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['fontStyle'], 'font-style' ); } if ( $has_font_weight_support && ! $should_skip_font_weight && isset( $block_attributes['style']['typography']['fontWeight'] ) ) { $typography_block_styles['fontWeight'] = wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['fontWeight'], 'font-weight' ); } if ( $has_line_height_support && ! $should_skip_line_height ) { $typography_block_styles['lineHeight'] = _wp_array_get( $block_attributes, array( 'style', 'typography', 'lineHeight' ) ); } if ( $has_text_decoration_support && ! $should_skip_text_decoration && isset( $block_attributes['style']['typography']['textDecoration'] ) ) { $typography_block_styles['textDecoration'] = wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['textDecoration'], 'text-decoration' ); } if ( $has_text_transform_support && ! $should_skip_text_transform && isset( $block_attributes['style']['typography']['textTransform'] ) ) { $typography_block_styles['textTransform'] = wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['textTransform'], 'text-transform' ); } if ( $has_letter_spacing_support && ! $should_skip_letter_spacing && isset( $block_attributes['style']['typography']['letterSpacing'] ) ) { $typography_block_styles['letterSpacing'] = wp_typography_get_preset_inline_style_value( $block_attributes['style']['typography']['letterSpacing'], 'letter-spacing' ); } $attributes = array(); $styles = wp_style_engine_get_styles( array( 'typography' => $typography_block_styles ), array( 'convert_vars_to_classnames' => true ) ); if ( ! empty( $styles['classnames'] ) ) { $attributes['class'] = $styles['classnames']; } if ( ! empty( $styles['css'] ) ) { $attributes['style'] = $styles['css']; } return $attributes; } function wp_typography_get_preset_inline_style_value( $style_value, $css_property ) { if ( empty( $style_value ) || ! str_contains( $style_value, "var:preset|{$css_property}|" ) ) { return $style_value; } $index_to_splice = strrpos( $style_value, '|' ) + 1; $slug = _wp_to_kebab_case( substr( $style_value, $index_to_splice ) ); return sprintf( 'var(--wp--preset--%s--%s);', $css_property, $slug ); } function wp_render_typography_support( $block_content, $block ) { if ( ! isset( $block['attrs']['style']['typography']['fontSize'] ) ) { return $block_content; } $custom_font_size = $block['attrs']['style']['typography']['fontSize']; $fluid_font_size = wp_get_typography_font_size_value( array( 'size' => $custom_font_size ) ); if ( ! empty( $fluid_font_size ) && $fluid_font_size !== $custom_font_size ) { return preg_replace( '/font-size\s*:\s*' . preg_quote( $custom_font_size, '/' ) . '\s*;?/', 'font-size:' . esc_attr( $fluid_font_size ) . ';', $block_content, 1 ); } return $block_content; } function wp_get_typography_value_and_unit( $raw_value, $options = array() ) { if ( ! is_string( $raw_value ) && ! is_int( $raw_value ) && ! is_float( $raw_value ) ) { _doing_it_wrong( __FUNCTION__, __( 'Raw size value must be a string, integer, or float.' ), '6.1.0' ); return null; } if ( empty( $raw_value ) ) { return null; } if ( is_numeric( $raw_value ) ) { $raw_value = $raw_value . 'px'; } $defaults = array( 'coerce_to' => '', 'root_size_value' => 16, 'acceptable_units' => array( 'rem', 'px', 'em' ), ); $options = wp_parse_args( $options, $defaults ); $acceptable_units_group = implode( '|', $options['acceptable_units'] ); $pattern = '/^(\d*\.?\d+)(' . $acceptable_units_group . '){1,1}$/'; preg_match( $pattern, $raw_value, $matches ); if ( ! isset( $matches[1] ) || ! isset( $matches[2] ) ) { return null; } $value = $matches[1]; $unit = $matches[2]; if ( 'px' === $options['coerce_to'] && ( 'em' === $unit || 'rem' === $unit ) ) { $value = $value * $options['root_size_value']; $unit = $options['coerce_to']; } if ( 'px' === $unit && ( 'em' === $options['coerce_to'] || 'rem' === $options['coerce_to'] ) ) { $value = $value / $options['root_size_value']; $unit = $options['coerce_to']; } if ( ( 'em' === $options['coerce_to'] || 'rem' === $options['coerce_to'] ) && ( 'em' === $unit || 'rem' === $unit ) ) { $unit = $options['coerce_to']; } return array( 'value' => round( $value, 3 ), 'unit' => $unit, ); } function wp_get_computed_fluid_typography_value( $args = array() ) { $maximum_viewport_width_raw = isset( $args['maximum_viewport_width'] ) ? $args['maximum_viewport_width'] : null; $minimum_viewport_width_raw = isset( $args['minimum_viewport_width'] ) ? $args['minimum_viewport_width'] : null; $maximum_font_size_raw = isset( $args['maximum_font_size'] ) ? $args['maximum_font_size'] : null; $minimum_font_size_raw = isset( $args['minimum_font_size'] ) ? $args['minimum_font_size'] : null; $scale_factor = isset( $args['scale_factor'] ) ? $args['scale_factor'] : null; $minimum_font_size = wp_get_typography_value_and_unit( $minimum_font_size_raw ); $font_size_unit = isset( $minimum_font_size['unit'] ) ? $minimum_font_size['unit'] : 'rem'; $maximum_font_size = wp_get_typography_value_and_unit( $maximum_font_size_raw, array( 'coerce_to' => $font_size_unit, ) ); if ( ! $maximum_font_size || ! $minimum_font_size ) { return null; } $minimum_font_size_rem = wp_get_typography_value_and_unit( $minimum_font_size_raw, array( 'coerce_to' => 'rem', ) ); $maximum_viewport_width = wp_get_typography_value_and_unit( $maximum_viewport_width_raw, array( 'coerce_to' => $font_size_unit, ) ); $minimum_viewport_width = wp_get_typography_value_and_unit( $minimum_viewport_width_raw, array( 'coerce_to' => $font_size_unit, ) ); $view_port_width_offset = round( $minimum_viewport_width['value'] / 100, 3 ) . $font_size_unit; $linear_factor = 100 * ( ( $maximum_font_size['value'] - $minimum_font_size['value'] ) / ( $maximum_viewport_width['value'] - $minimum_viewport_width['value'] ) ); $linear_factor_scaled = round( $linear_factor * $scale_factor, 3 ); $linear_factor_scaled = empty( $linear_factor_scaled ) ? 1 : $linear_factor_scaled; $fluid_target_font_size = implode( '', $minimum_font_size_rem ) . " + ((1vw - $view_port_width_offset) * $linear_factor_scaled)"; return "clamp($minimum_font_size_raw, $fluid_target_font_size, $maximum_font_size_raw)"; } function wp_get_typography_font_size_value( $preset, $should_use_fluid_typography = false ) { if ( ! isset( $preset['size'] ) ) { return null; } if ( empty( $preset['size'] ) ) { return $preset['size']; } $typography_settings = wp_get_global_settings( array( 'typography' ) ); $should_use_fluid_typography = isset( $typography_settings['fluid'] ) && true === $typography_settings['fluid'] ? true : $should_use_fluid_typography; if ( ! $should_use_fluid_typography ) { return $preset['size']; } $default_maximum_viewport_width = '1600px'; $default_minimum_viewport_width = '768px'; $default_minimum_font_size_factor = 0.75; $default_scale_factor = 1; $default_minimum_font_size_limit = '14px'; $fluid_font_size_settings = isset( $preset['fluid'] ) ? $preset['fluid'] : null; if ( false === $fluid_font_size_settings ) { return $preset['size']; } $minimum_font_size_raw = isset( $fluid_font_size_settings['min'] ) ? $fluid_font_size_settings['min'] : null; $maximum_font_size_raw = isset( $fluid_font_size_settings['max'] ) ? $fluid_font_size_settings['max'] : null; $preferred_size = wp_get_typography_value_and_unit( $preset['size'] ); if ( empty( $preferred_size['unit'] ) ) { return $preset['size']; } $minimum_font_size_limit = wp_get_typography_value_and_unit( $default_minimum_font_size_limit, array( 'coerce_to' => $preferred_size['unit'], ) ); if ( ! empty( $minimum_font_size_limit ) && ( ! $minimum_font_size_raw && ! $maximum_font_size_raw ) ) { if ( $preferred_size['value'] <= $minimum_font_size_limit['value'] ) { return $preset['size']; } } if ( ! $maximum_font_size_raw ) { $maximum_font_size_raw = $preferred_size['value'] . $preferred_size['unit']; } if ( ! $minimum_font_size_raw ) { $calculated_minimum_font_size = round( $preferred_size['value'] * $default_minimum_font_size_factor, 3 ); if ( ! empty( $minimum_font_size_limit ) && $calculated_minimum_font_size <= $minimum_font_size_limit['value'] ) { $minimum_font_size_raw = $minimum_font_size_limit['value'] . $minimum_font_size_limit['unit']; } else { $minimum_font_size_raw = $calculated_minimum_font_size . $preferred_size['unit']; } } $fluid_font_size_value = wp_get_computed_fluid_typography_value( array( 'minimum_viewport_width' => $default_minimum_viewport_width, 'maximum_viewport_width' => $default_maximum_viewport_width, 'minimum_font_size' => $minimum_font_size_raw, 'maximum_font_size' => $maximum_font_size_raw, 'scale_factor' => $default_scale_factor, ) ); if ( ! empty( $fluid_font_size_value ) ) { return $fluid_font_size_value; } return $preset['size']; } WP_Block_Supports::get_instance()->register( 'typography', array( 'register_attribute' => 'wp_register_typography_support', 'apply' => 'wp_apply_typography_support', ) ); 