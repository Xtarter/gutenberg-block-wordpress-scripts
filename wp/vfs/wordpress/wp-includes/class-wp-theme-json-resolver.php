<?php
 class WP_Theme_JSON_Resolver { protected static $blocks_cache = array( 'core' => array(), 'blocks' => array(), 'theme' => array(), 'user' => array(), ); protected static $core = null; protected static $blocks = null; protected static $theme = null; protected static $theme_has_support = null; protected static $user = null; protected static $user_custom_post_type_id = null; protected static $i18n_schema = null; protected static $theme_json_file_cache = array(); protected static function read_json_file( $file_path ) { if ( $file_path ) { if ( array_key_exists( $file_path, static::$theme_json_file_cache ) ) { return static::$theme_json_file_cache[ $file_path ]; } $decoded_file = wp_json_file_decode( $file_path, array( 'associative' => true ) ); if ( is_array( $decoded_file ) ) { static::$theme_json_file_cache[ $file_path ] = $decoded_file; return static::$theme_json_file_cache[ $file_path ]; } } return array(); } public static function get_fields_to_translate() { _deprecated_function( __METHOD__, '5.9.0' ); return array(); } protected static function translate( $theme_json, $domain = 'default' ) { if ( null === static::$i18n_schema ) { $i18n_schema = wp_json_file_decode( __DIR__ . '/theme-i18n.json' ); static::$i18n_schema = null === $i18n_schema ? array() : $i18n_schema; } return translate_settings_using_i18n_schema( static::$i18n_schema, $theme_json, $domain ); } public static function get_core_data() { if ( null !== static::$core && static::has_same_registered_blocks( 'core' ) ) { return static::$core; } $config = static::read_json_file( __DIR__ . '/theme.json' ); $config = static::translate( $config ); $theme_json = apply_filters( 'wp_theme_json_data_default', new WP_Theme_JSON_Data( $config, 'default' ) ); $config = $theme_json->get_data(); static::$core = new WP_Theme_JSON( $config, 'default' ); return static::$core; } protected static function has_same_registered_blocks( $origin ) { if ( ! isset( static::$blocks_cache[ $origin ] ) ) { return false; } $registry = WP_Block_Type_Registry::get_instance(); $blocks = $registry->get_all_registered(); $block_diff = array_diff_key( $blocks, static::$blocks_cache[ $origin ] ); if ( empty( $block_diff ) ) { return true; } foreach ( $blocks as $block_name => $block_type ) { static::$blocks_cache[ $origin ][ $block_name ] = true; } return false; } public static function get_theme_data( $deprecated = array(), $options = array() ) { if ( ! empty( $deprecated ) ) { _deprecated_argument( __METHOD__, '5.9.0' ); } $options = wp_parse_args( $options, array( 'with_supports' => true ) ); if ( null === static::$theme || ! static::has_same_registered_blocks( 'theme' ) ) { $theme_json_file = static::get_file_path_from_theme( 'theme.json' ); $wp_theme = wp_get_theme(); if ( '' !== $theme_json_file ) { $theme_json_data = static::read_json_file( $theme_json_file ); $theme_json_data = static::translate( $theme_json_data, $wp_theme->get( 'TextDomain' ) ); } else { $theme_json_data = array(); } $theme_json = apply_filters( 'wp_theme_json_data_theme', new WP_Theme_JSON_Data( $theme_json_data, 'theme' ) ); $theme_json_data = $theme_json->get_data(); static::$theme = new WP_Theme_JSON( $theme_json_data ); if ( $wp_theme->parent() ) { $parent_theme_json_file = static::get_file_path_from_theme( 'theme.json', true ); if ( '' !== $parent_theme_json_file ) { $parent_theme_json_data = static::read_json_file( $parent_theme_json_file ); $parent_theme_json_data = static::translate( $parent_theme_json_data, $wp_theme->parent()->get( 'TextDomain' ) ); $parent_theme = new WP_Theme_JSON( $parent_theme_json_data ); $parent_theme->merge( static::$theme ); static::$theme = $parent_theme; } } } if ( ! $options['with_supports'] ) { return static::$theme; } $theme_support_data = WP_Theme_JSON::get_from_editor_settings( get_default_block_editor_settings() ); if ( ! static::theme_has_support() ) { if ( ! isset( $theme_support_data['settings']['color'] ) ) { $theme_support_data['settings']['color'] = array(); } $default_palette = false; if ( current_theme_supports( 'default-color-palette' ) ) { $default_palette = true; } if ( ! isset( $theme_support_data['settings']['color']['palette'] ) ) { $default_palette = true; } $theme_support_data['settings']['color']['defaultPalette'] = $default_palette; $default_gradients = false; if ( current_theme_supports( 'default-gradient-presets' ) ) { $default_gradients = true; } if ( ! isset( $theme_support_data['settings']['color']['gradients'] ) ) { $default_gradients = true; } $theme_support_data['settings']['color']['defaultGradients'] = $default_gradients; $theme_support_data['settings']['color']['defaultDuotone'] = false; } $with_theme_supports = new WP_Theme_JSON( $theme_support_data ); $with_theme_supports->merge( static::$theme ); return $with_theme_supports; } public static function get_block_data() { $registry = WP_Block_Type_Registry::get_instance(); $blocks = $registry->get_all_registered(); if ( null !== static::$blocks && static::has_same_registered_blocks( 'blocks' ) ) { return static::$blocks; } $config = array( 'version' => 2 ); foreach ( $blocks as $block_name => $block_type ) { if ( isset( $block_type->supports['__experimentalStyle'] ) ) { $config['styles']['blocks'][ $block_name ] = static::remove_json_comments( $block_type->supports['__experimentalStyle'] ); } if ( isset( $block_type->supports['spacing']['blockGap']['__experimentalDefault'] ) && null === _wp_array_get( $config, array( 'styles', 'blocks', $block_name, 'spacing', 'blockGap' ), null ) ) { $config['styles']['blocks'][ $block_name ]['spacing']['blockGap'] = null; } } $theme_json = apply_filters( 'wp_theme_json_data_blocks', new WP_Theme_JSON_Data( $config, 'blocks' ) ); $config = $theme_json->get_data(); static::$blocks = new WP_Theme_JSON( $config, 'blocks' ); return static::$blocks; } private static function remove_json_comments( $array ) { unset( $array['//'] ); foreach ( $array as $k => $v ) { if ( is_array( $v ) ) { $array[ $k ] = static::remove_json_comments( $v ); } } return $array; } public static function get_user_data_from_wp_global_styles( $theme, $create_post = false, $post_status_filter = array( 'publish' ) ) { if ( ! $theme instanceof WP_Theme ) { $theme = wp_get_theme(); } if ( $theme->get_stylesheet() === get_stylesheet() && ! static::theme_has_support() ) { return array(); } $user_cpt = array(); $post_type_filter = 'wp_global_styles'; $stylesheet = $theme->get_stylesheet(); $args = array( 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'desc', 'post_type' => $post_type_filter, 'post_status' => $post_status_filter, 'ignore_sticky_posts' => true, 'no_found_rows' => true, 'tax_query' => array( array( 'taxonomy' => 'wp_theme', 'field' => 'name', 'terms' => $stylesheet, ), ), ); $global_style_query = new WP_Query(); $recent_posts = $global_style_query->query( $args ); if ( count( $recent_posts ) === 1 ) { $user_cpt = get_post( $recent_posts[0], ARRAY_A ); } elseif ( $create_post ) { $cpt_post_id = wp_insert_post( array( 'post_content' => '{"version": ' . WP_Theme_JSON::LATEST_SCHEMA . ', "isGlobalStylesUserThemeJSON": true }', 'post_status' => 'publish', 'post_title' => 'Custom Styles', 'post_type' => $post_type_filter, 'post_name' => sprintf( 'wp-global-styles-%s', urlencode( $stylesheet ) ), 'tax_input' => array( 'wp_theme' => array( $stylesheet ), ), ), true ); if ( ! is_wp_error( $cpt_post_id ) ) { $user_cpt = get_post( $cpt_post_id, ARRAY_A ); } } return $user_cpt; } public static function get_user_data() { if ( null !== static::$user && static::has_same_registered_blocks( 'user' ) ) { return static::$user; } $config = array(); $user_cpt = static::get_user_data_from_wp_global_styles( wp_get_theme() ); if ( array_key_exists( 'post_content', $user_cpt ) ) { $decoded_data = json_decode( $user_cpt['post_content'], true ); $json_decoding_error = json_last_error(); if ( JSON_ERROR_NONE !== $json_decoding_error ) { trigger_error( 'Error when decoding a theme.json schema for user data. ' . json_last_error_msg() ); $theme_json = apply_filters( 'wp_theme_json_data_user', new WP_Theme_JSON_Data( $config, 'custom' ) ); $config = $theme_json->get_data(); return new WP_Theme_JSON( $config, 'custom' ); } if ( is_array( $decoded_data ) && isset( $decoded_data['isGlobalStylesUserThemeJSON'] ) && $decoded_data['isGlobalStylesUserThemeJSON'] ) { unset( $decoded_data['isGlobalStylesUserThemeJSON'] ); $config = $decoded_data; } } $theme_json = apply_filters( 'wp_theme_json_data_user', new WP_Theme_JSON_Data( $config, 'custom' ) ); $config = $theme_json->get_data(); static::$user = new WP_Theme_JSON( $config, 'custom' ); return static::$user; } public static function get_merged_data( $origin = 'custom' ) { if ( is_array( $origin ) ) { _deprecated_argument( __FUNCTION__, '5.9.0' ); } $result = static::get_core_data(); $result->merge( static::get_block_data() ); $result->merge( static::get_theme_data() ); if ( 'custom' === $origin ) { $result->merge( static::get_user_data() ); } $result->set_spacing_sizes(); return $result; } public static function get_user_global_styles_post_id() { if ( null !== static::$user_custom_post_type_id ) { return static::$user_custom_post_type_id; } $user_cpt = static::get_user_data_from_wp_global_styles( wp_get_theme(), true ); if ( array_key_exists( 'ID', $user_cpt ) ) { static::$user_custom_post_type_id = $user_cpt['ID']; } return static::$user_custom_post_type_id; } public static function theme_has_support() { if ( ! isset( static::$theme_has_support ) ) { static::$theme_has_support = ( static::get_file_path_from_theme( 'theme.json' ) !== '' || static::get_file_path_from_theme( 'theme.json', true ) !== '' ); } return static::$theme_has_support; } protected static function get_file_path_from_theme( $file_name, $template = false ) { $path = $template ? get_template_directory() : get_stylesheet_directory(); $candidate = $path . '/' . $file_name; return is_readable( $candidate ) ? $candidate : ''; } public static function clean_cached_data() { static::$core = null; static::$blocks = null; static::$blocks_cache = array( 'core' => array(), 'blocks' => array(), 'theme' => array(), 'user' => array(), ); static::$theme = null; static::$user = null; static::$user_custom_post_type_id = null; static::$theme_has_support = null; static::$i18n_schema = null; } public static function get_style_variations() { $variations = array(); $base_directory = get_stylesheet_directory() . '/styles'; if ( is_dir( $base_directory ) ) { $nested_files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base_directory ) ); $nested_html_files = iterator_to_array( new RegexIterator( $nested_files, '/^.+\.json$/i', RecursiveRegexIterator::GET_MATCH ) ); ksort( $nested_html_files ); foreach ( $nested_html_files as $path => $file ) { $decoded_file = wp_json_file_decode( $path, array( 'associative' => true ) ); if ( is_array( $decoded_file ) ) { $translated = static::translate( $decoded_file, wp_get_theme()->get( 'TextDomain' ) ); $variation = ( new WP_Theme_JSON( $translated ) )->get_raw_data(); if ( empty( $variation['title'] ) ) { $variation['title'] = basename( $path, '.json' ); } $variations[] = $variation; } } } return $variations; } } 