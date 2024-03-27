<?php
 function render_block_core_template_part( $attributes ) { static $seen_ids = array(); $template_part_id = null; $content = null; $area = WP_TEMPLATE_PART_AREA_UNCATEGORIZED; if ( isset( $attributes['slug'] ) && isset( $attributes['theme'] ) && wp_get_theme()->get_stylesheet() === $attributes['theme'] ) { $template_part_id = $attributes['theme'] . '//' . $attributes['slug']; $template_part_query = new WP_Query( array( 'post_type' => 'wp_template_part', 'post_status' => 'publish', 'post_name__in' => array( $attributes['slug'] ), 'tax_query' => array( array( 'taxonomy' => 'wp_theme', 'field' => 'name', 'terms' => $attributes['theme'], ), ), 'posts_per_page' => 1, 'no_found_rows' => true, ) ); $template_part_post = $template_part_query->have_posts() ? $template_part_query->next_post() : null; if ( $template_part_post ) { $content = $template_part_post->post_content; $area_terms = get_the_terms( $template_part_post, 'wp_template_part_area' ); if ( ! is_wp_error( $area_terms ) && false !== $area_terms ) { $area = $area_terms[0]->name; } do_action( 'render_block_core_template_part_post', $template_part_id, $attributes, $template_part_post, $content ); } else { $parent_theme_folders = get_block_theme_folders( get_template() ); $child_theme_folders = get_block_theme_folders( get_stylesheet() ); $child_theme_part_file_path = get_theme_file_path( '/' . $child_theme_folders['wp_template_part'] . '/' . $attributes['slug'] . '.html' ); $parent_theme_part_file_path = get_theme_file_path( '/' . $parent_theme_folders['wp_template_part'] . '/' . $attributes['slug'] . '.html' ); $template_part_file_path = 0 === validate_file( $attributes['slug'] ) && file_exists( $child_theme_part_file_path ) ? $child_theme_part_file_path : $parent_theme_part_file_path; if ( 0 === validate_file( $attributes['slug'] ) && file_exists( $template_part_file_path ) ) { $content = file_get_contents( $template_part_file_path ); $content = is_string( $content ) && '' !== $content ? _inject_theme_attribute_in_block_template_content( $content ) : ''; } if ( '' !== $content && null !== $content ) { do_action( 'render_block_core_template_part_file', $template_part_id, $attributes, $template_part_file_path, $content ); } else { do_action( 'render_block_core_template_part_none', $template_part_id, $attributes, $template_part_file_path ); } } } $is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY; if ( is_null( $content ) && $is_debug ) { if ( ! isset( $attributes['slug'] ) ) { return; } return sprintf( __( 'Template part has been deleted or is unavailable: %s' ), $attributes['slug'] ); } if ( isset( $seen_ids[ $template_part_id ] ) ) { return $is_debug ? __( '[block rendering halted]' ) : ''; } $seen_ids[ $template_part_id ] = true; $content = do_blocks( $content ); unset( $seen_ids[ $template_part_id ] ); $content = wptexturize( $content ); $content = convert_smilies( $content ); $content = shortcode_unautop( $content ); $content = wp_filter_content_tags( $content ); $content = do_shortcode( $content ); global $wp_embed; $content = $wp_embed->autoembed( $content ); if ( empty( $attributes['tagName'] ) ) { $defined_areas = get_allowed_block_template_part_areas(); $area_tag = 'div'; foreach ( $defined_areas as $defined_area ) { if ( $defined_area['area'] === $area && isset( $defined_area['area_tag'] ) ) { $area_tag = $defined_area['area_tag']; } } $html_tag = $area_tag; } else { $html_tag = esc_attr( $attributes['tagName'] ); } $wrapper_attributes = get_block_wrapper_attributes(); return "<$html_tag $wrapper_attributes>" . str_replace( ']]>', ']]&gt;', $content ) . "</$html_tag>"; } function build_template_part_block_area_variations() { $variations = array(); $defined_areas = get_allowed_block_template_part_areas(); foreach ( $defined_areas as $area ) { if ( 'uncategorized' !== $area['area'] ) { $variations[] = array( 'name' => $area['area'], 'title' => $area['label'], 'description' => $area['description'], 'attributes' => array( 'area' => $area['area'], ), 'scope' => array( 'inserter' ), 'icon' => $area['icon'], ); } } return $variations; } function build_template_part_block_instance_variations() { if ( wp_installing() ) { return array(); } if ( ! current_theme_supports( 'block-templates' ) && ! current_theme_supports( 'block-template-parts' ) ) { return array(); } $variations = array(); $template_parts = get_block_templates( array( 'post_type' => 'wp_template_part', ), 'wp_template_part' ); $defined_areas = get_allowed_block_template_part_areas(); $icon_by_area = array_combine( array_column( $defined_areas, 'area' ), array_column( $defined_areas, 'icon' ) ); foreach ( $template_parts as $template_part ) { $variations[] = array( 'name' => sanitize_title( $template_part->slug ), 'title' => $template_part->title, 'description' => $template_part->description || '&nbsp;', 'attributes' => array( 'slug' => $template_part->slug, 'theme' => $template_part->theme, 'area' => $template_part->area, ), 'scope' => array( 'inserter' ), 'icon' => $icon_by_area[ $template_part->area ], 'example' => array( 'attributes' => array( 'slug' => $template_part->slug, 'theme' => $template_part->theme, 'area' => $template_part->area, ), ), ); } return $variations; } function build_template_part_block_variations() { return array_merge( build_template_part_block_area_variations(), build_template_part_block_instance_variations() ); } function register_block_core_template_part() { register_block_type_from_metadata( __DIR__ . '/template-part', array( 'render_callback' => 'render_block_core_template_part', 'variations' => build_template_part_block_variations(), ) ); } add_action( 'init', 'register_block_core_template_part' ); 