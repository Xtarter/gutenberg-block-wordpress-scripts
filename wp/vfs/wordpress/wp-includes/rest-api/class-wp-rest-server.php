<?php
 class WP_REST_Server { const READABLE = 'GET'; const CREATABLE = 'POST'; const EDITABLE = 'POST, PUT, PATCH'; const DELETABLE = 'DELETE'; const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE'; protected $namespaces = array(); protected $endpoints = array(); protected $route_options = array(); protected $embed_cache = array(); public function __construct() { $this->endpoints = array( '/' => array( 'callback' => array( $this, 'get_index' ), 'methods' => 'GET', 'args' => array( 'context' => array( 'default' => 'view', ), ), ), '/batch/v1' => array( 'callback' => array( $this, 'serve_batch_request_v1' ), 'methods' => 'POST', 'args' => array( 'validation' => array( 'type' => 'string', 'enum' => array( 'require-all-validate', 'normal' ), 'default' => 'normal', ), 'requests' => array( 'required' => true, 'type' => 'array', 'maxItems' => $this->get_max_batch_size(), 'items' => array( 'type' => 'object', 'properties' => array( 'method' => array( 'type' => 'string', 'enum' => array( 'POST', 'PUT', 'PATCH', 'DELETE' ), 'default' => 'POST', ), 'path' => array( 'type' => 'string', 'required' => true, ), 'body' => array( 'type' => 'object', 'properties' => array(), 'additionalProperties' => true, ), 'headers' => array( 'type' => 'object', 'properties' => array(), 'additionalProperties' => array( 'type' => array( 'string', 'array' ), 'items' => array( 'type' => 'string', ), ), ), ), ), ), ), ), ); } public function check_authentication() { return apply_filters( 'rest_authentication_errors', null ); } protected function error_to_response( $error ) { return rest_convert_error_to_response( $error ); } protected function json_error( $code, $message, $status = null ) { if ( $status ) { $this->set_status( $status ); } $error = compact( 'code', 'message' ); return wp_json_encode( $error ); } protected function get_json_encode_options( WP_REST_Request $request ) { $options = 0; if ( $request->has_param( '_pretty' ) ) { $options |= JSON_PRETTY_PRINT; } return apply_filters( 'rest_json_encode_options', $options, $request ); } public function serve_request( $path = null ) { global $current_user; if ( $current_user instanceof WP_User && ! $current_user->exists() ) { $current_user = null; } $jsonp_enabled = apply_filters( 'rest_jsonp_enabled', true ); $jsonp_callback = false; if ( isset( $_GET['_jsonp'] ) ) { $jsonp_callback = $_GET['_jsonp']; } $content_type = ( $jsonp_callback && $jsonp_enabled ) ? 'application/javascript' : 'application/json'; $this->send_header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) ); $this->send_header( 'X-Robots-Tag', 'noindex' ); $api_root = get_rest_url(); if ( ! empty( $api_root ) ) { $this->send_header( 'Link', '<' . sanitize_url( $api_root ) . '>; rel="https://api.w.org/"' ); } $this->send_header( 'X-Content-Type-Options', 'nosniff' ); $expose_headers = array( 'X-WP-Total', 'X-WP-TotalPages', 'Link' ); $expose_headers = apply_filters( 'rest_exposed_cors_headers', $expose_headers ); $this->send_header( 'Access-Control-Expose-Headers', implode( ', ', $expose_headers ) ); $allow_headers = array( 'Authorization', 'X-WP-Nonce', 'Content-Disposition', 'Content-MD5', 'Content-Type', ); $allow_headers = apply_filters( 'rest_allowed_cors_headers', $allow_headers ); $this->send_header( 'Access-Control-Allow-Headers', implode( ', ', $allow_headers ) ); $send_no_cache_headers = apply_filters( 'rest_send_nocache_headers', is_user_logged_in() ); if ( $send_no_cache_headers ) { foreach ( wp_get_nocache_headers() as $header => $header_value ) { if ( empty( $header_value ) ) { $this->remove_header( $header ); } else { $this->send_header( $header, $header_value ); } } } apply_filters_deprecated( 'rest_enabled', array( true ), '4.7.0', 'rest_authentication_errors', sprintf( __( 'The REST API can no longer be completely disabled, the %s filter can be used to restrict access to the API, instead.' ), 'rest_authentication_errors' ) ); if ( $jsonp_callback ) { if ( ! $jsonp_enabled ) { echo $this->json_error( 'rest_callback_disabled', __( 'JSONP support is disabled on this site.' ), 400 ); return false; } if ( ! wp_check_jsonp_callback( $jsonp_callback ) ) { echo $this->json_error( 'rest_callback_invalid', __( 'Invalid JSONP callback function.' ), 400 ); return false; } } if ( empty( $path ) ) { if ( isset( $_SERVER['PATH_INFO'] ) ) { $path = $_SERVER['PATH_INFO']; } else { $path = '/'; } } $request = new WP_REST_Request( $_SERVER['REQUEST_METHOD'], $path ); $request->set_query_params( wp_unslash( $_GET ) ); $request->set_body_params( wp_unslash( $_POST ) ); $request->set_file_params( $_FILES ); $request->set_headers( $this->get_headers( wp_unslash( $_SERVER ) ) ); $request->set_body( self::get_raw_data() ); if ( isset( $_GET['_method'] ) ) { $request->set_method( $_GET['_method'] ); } elseif ( isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ) { $request->set_method( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ); } $result = $this->check_authentication(); if ( ! is_wp_error( $result ) ) { $result = $this->dispatch( $request ); } $result = rest_ensure_response( $result ); if ( is_wp_error( $result ) ) { $result = $this->error_to_response( $result ); } $result = apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $this, $request ); if ( isset( $_GET['_envelope'] ) ) { $embed = isset( $_GET['_embed'] ) ? rest_parse_embed_param( $_GET['_embed'] ) : false; $result = $this->envelope_response( $result, $embed ); } $headers = $result->get_headers(); $this->send_headers( $headers ); $code = $result->get_status(); $this->set_status( $code ); $served = apply_filters( 'rest_pre_serve_request', false, $result, $request, $this ); if ( ! $served ) { if ( 'HEAD' === $request->get_method() ) { return null; } $embed = isset( $_GET['_embed'] ) ? rest_parse_embed_param( $_GET['_embed'] ) : false; $result = $this->response_to_data( $result, $embed ); $result = apply_filters( 'rest_pre_echo_response', $result, $this, $request ); if ( 204 === $code || null === $result ) { return null; } $result = wp_json_encode( $result, $this->get_json_encode_options( $request ) ); $json_error_message = $this->get_json_last_error(); if ( $json_error_message ) { $this->set_status( 500 ); $json_error_obj = new WP_Error( 'rest_encode_error', $json_error_message, array( 'status' => 500 ) ); $result = $this->error_to_response( $json_error_obj ); $result = wp_json_encode( $result->data, $this->get_json_encode_options( $request ) ); } if ( $jsonp_callback ) { echo '/**/' . $jsonp_callback . '(' . $result . ')'; } else { echo $result; } } return null; } public function response_to_data( $response, $embed ) { $data = $response->get_data(); $links = self::get_compact_response_links( $response ); if ( ! empty( $links ) ) { $data['_links'] = $links; } if ( $embed ) { $this->embed_cache = array(); if ( wp_is_numeric_array( $data ) ) { foreach ( $data as $key => $item ) { $data[ $key ] = $this->embed_links( $item, $embed ); } } else { $data = $this->embed_links( $data, $embed ); } $this->embed_cache = array(); } return $data; } public static function get_response_links( $response ) { $links = $response->get_links(); if ( empty( $links ) ) { return array(); } $data = array(); foreach ( $links as $rel => $items ) { $data[ $rel ] = array(); foreach ( $items as $item ) { $attributes = $item['attributes']; $attributes['href'] = $item['href']; $data[ $rel ][] = $attributes; } } return $data; } public static function get_compact_response_links( $response ) { $links = self::get_response_links( $response ); if ( empty( $links ) ) { return array(); } $curies = $response->get_curies(); $used_curies = array(); foreach ( $links as $rel => $items ) { foreach ( $curies as $curie ) { $href_prefix = substr( $curie['href'], 0, strpos( $curie['href'], '{rel}' ) ); if ( strpos( $rel, $href_prefix ) !== 0 ) { continue; } $rel_regex = str_replace( '\{rel\}', '(.+)', preg_quote( $curie['href'], '!' ) ); preg_match( '!' . $rel_regex . '!', $rel, $matches ); if ( $matches ) { $new_rel = $curie['name'] . ':' . $matches[1]; $used_curies[ $curie['name'] ] = $curie; $links[ $new_rel ] = $items; unset( $links[ $rel ] ); break; } } } if ( $used_curies ) { $links['curies'] = array_values( $used_curies ); } return $links; } protected function embed_links( $data, $embed = true ) { if ( empty( $data['_links'] ) ) { return $data; } $embedded = array(); foreach ( $data['_links'] as $rel => $links ) { if ( is_array( $embed ) && ! in_array( $rel, $embed, true ) ) { continue; } $embeds = array(); foreach ( $links as $item ) { if ( empty( $item['embeddable'] ) ) { $embeds[] = array(); continue; } if ( ! array_key_exists( $item['href'], $this->embed_cache ) ) { $request = WP_REST_Request::from_url( $item['href'] ); if ( ! $request ) { $embeds[] = array(); continue; } if ( empty( $request['context'] ) ) { $request['context'] = 'embed'; } $response = $this->dispatch( $request ); $response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this, $request ); $this->embed_cache[ $item['href'] ] = $this->response_to_data( $response, false ); } $embeds[] = $this->embed_cache[ $item['href'] ]; } $has_links = count( array_filter( $embeds ) ); if ( $has_links ) { $embedded[ $rel ] = $embeds; } } if ( ! empty( $embedded ) ) { $data['_embedded'] = $embedded; } return $data; } public function envelope_response( $response, $embed ) { $envelope = array( 'body' => $this->response_to_data( $response, $embed ), 'status' => $response->get_status(), 'headers' => $response->get_headers(), ); $envelope = apply_filters( 'rest_envelope_response', $envelope, $response ); return rest_ensure_response( $envelope ); } public function register_route( $namespace, $route, $route_args, $override = false ) { if ( ! isset( $this->namespaces[ $namespace ] ) ) { $this->namespaces[ $namespace ] = array(); $this->register_route( $namespace, '/' . $namespace, array( array( 'methods' => self::READABLE, 'callback' => array( $this, 'get_namespace_index' ), 'args' => array( 'namespace' => array( 'default' => $namespace, ), 'context' => array( 'default' => 'view', ), ), ), ) ); } $this->namespaces[ $namespace ][ $route ] = true; $route_args['namespace'] = $namespace; if ( $override || empty( $this->endpoints[ $route ] ) ) { $this->endpoints[ $route ] = $route_args; } else { $this->endpoints[ $route ] = array_merge( $this->endpoints[ $route ], $route_args ); } } public function get_routes( $namespace = '' ) { $endpoints = $this->endpoints; if ( $namespace ) { $endpoints = wp_list_filter( $endpoints, array( 'namespace' => $namespace ) ); } $endpoints = apply_filters( 'rest_endpoints', $endpoints ); $defaults = array( 'methods' => '', 'accept_json' => false, 'accept_raw' => false, 'show_in_index' => true, 'args' => array(), ); foreach ( $endpoints as $route => &$handlers ) { if ( isset( $handlers['callback'] ) ) { $handlers = array( $handlers ); } if ( ! isset( $this->route_options[ $route ] ) ) { $this->route_options[ $route ] = array(); } foreach ( $handlers as $key => &$handler ) { if ( ! is_numeric( $key ) ) { $this->route_options[ $route ][ $key ] = $handler; unset( $handlers[ $key ] ); continue; } $handler = wp_parse_args( $handler, $defaults ); if ( is_string( $handler['methods'] ) ) { $methods = explode( ',', $handler['methods'] ); } elseif ( is_array( $handler['methods'] ) ) { $methods = $handler['methods']; } else { $methods = array(); } $handler['methods'] = array(); foreach ( $methods as $method ) { $method = strtoupper( trim( $method ) ); $handler['methods'][ $method ] = true; } } } return $endpoints; } public function get_namespaces() { return array_keys( $this->namespaces ); } public function get_route_options( $route ) { if ( ! isset( $this->route_options[ $route ] ) ) { return null; } return $this->route_options[ $route ]; } public function dispatch( $request ) { $result = apply_filters( 'rest_pre_dispatch', null, $this, $request ); if ( ! empty( $result ) ) { return $result; } $error = null; $matched = $this->match_request_to_handler( $request ); if ( is_wp_error( $matched ) ) { return $this->error_to_response( $matched ); } list( $route, $handler ) = $matched; if ( ! is_callable( $handler['callback'] ) ) { $error = new WP_Error( 'rest_invalid_handler', __( 'The handler for the route is invalid.' ), array( 'status' => 500 ) ); } if ( ! is_wp_error( $error ) ) { $check_required = $request->has_valid_params(); if ( is_wp_error( $check_required ) ) { $error = $check_required; } else { $check_sanitized = $request->sanitize_params(); if ( is_wp_error( $check_sanitized ) ) { $error = $check_sanitized; } } } return $this->respond_to_request( $request, $route, $handler, $error ); } protected function match_request_to_handler( $request ) { $method = $request->get_method(); $path = $request->get_route(); $with_namespace = array(); foreach ( $this->get_namespaces() as $namespace ) { if ( 0 === strpos( trailingslashit( ltrim( $path, '/' ) ), $namespace ) ) { $with_namespace[] = $this->get_routes( $namespace ); } } if ( $with_namespace ) { $routes = array_merge( ...$with_namespace ); } else { $routes = $this->get_routes(); } foreach ( $routes as $route => $handlers ) { $match = preg_match( '@^' . $route . '$@i', $path, $matches ); if ( ! $match ) { continue; } $args = array(); foreach ( $matches as $param => $value ) { if ( ! is_int( $param ) ) { $args[ $param ] = $value; } } foreach ( $handlers as $handler ) { $callback = $handler['callback']; $response = null; $checked_method = $method; if ( 'HEAD' === $method && empty( $handler['methods']['HEAD'] ) ) { $checked_method = 'GET'; } if ( empty( $handler['methods'][ $checked_method ] ) ) { continue; } if ( ! is_callable( $callback ) ) { return array( $route, $handler ); } $request->set_url_params( $args ); $request->set_attributes( $handler ); $defaults = array(); foreach ( $handler['args'] as $arg => $options ) { if ( isset( $options['default'] ) ) { $defaults[ $arg ] = $options['default']; } } $request->set_default_params( $defaults ); return array( $route, $handler ); } } return new WP_Error( 'rest_no_route', __( 'No route was found matching the URL and request method.' ), array( 'status' => 404 ) ); } protected function respond_to_request( $request, $route, $handler, $response ) { $response = apply_filters( 'rest_request_before_callbacks', $response, $handler, $request ); if ( ! is_wp_error( $response ) && ! empty( $handler['permission_callback'] ) ) { $permission = call_user_func( $handler['permission_callback'], $request ); if ( is_wp_error( $permission ) ) { $response = $permission; } elseif ( false === $permission || null === $permission ) { $response = new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to do that.' ), array( 'status' => rest_authorization_required_code() ) ); } } if ( ! is_wp_error( $response ) ) { $dispatch_result = apply_filters( 'rest_dispatch_request', null, $request, $route, $handler ); if ( null !== $dispatch_result ) { $response = $dispatch_result; } else { $response = call_user_func( $handler['callback'], $request ); } } $response = apply_filters( 'rest_request_after_callbacks', $response, $handler, $request ); if ( is_wp_error( $response ) ) { $response = $this->error_to_response( $response ); } else { $response = rest_ensure_response( $response ); } $response->set_matched_route( $route ); $response->set_matched_handler( $handler ); return $response; } protected function get_json_last_error() { $last_error_code = json_last_error(); if ( JSON_ERROR_NONE === $last_error_code || empty( $last_error_code ) ) { return false; } return json_last_error_msg(); } public function get_index( $request ) { $available = array( 'name' => get_option( 'blogname' ), 'description' => get_option( 'blogdescription' ), 'url' => get_option( 'siteurl' ), 'home' => home_url(), 'gmt_offset' => get_option( 'gmt_offset' ), 'timezone_string' => get_option( 'timezone_string' ), 'namespaces' => array_keys( $this->namespaces ), 'authentication' => array(), 'routes' => $this->get_data_for_routes( $this->get_routes(), $request['context'] ), ); $response = new WP_REST_Response( $available ); $response->add_link( 'help', 'https://developer.wordpress.org/rest-api/' ); $this->add_active_theme_link_to_index( $response ); $this->add_site_logo_to_index( $response ); $this->add_site_icon_to_index( $response ); return apply_filters( 'rest_index', $response, $request ); } protected function add_active_theme_link_to_index( WP_REST_Response $response ) { $should_add = current_user_can( 'switch_themes' ) || current_user_can( 'manage_network_themes' ); if ( ! $should_add && current_user_can( 'edit_posts' ) ) { $should_add = true; } if ( ! $should_add ) { foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) { if ( current_user_can( $post_type->cap->edit_posts ) ) { $should_add = true; break; } } } if ( $should_add ) { $theme = wp_get_theme(); $response->add_link( 'https://api.w.org/active-theme', rest_url( 'wp/v2/themes/' . $theme->get_stylesheet() ) ); } } protected function add_site_logo_to_index( WP_REST_Response $response ) { $site_logo_id = get_theme_mod( 'custom_logo', 0 ); $this->add_image_to_index( $response, $site_logo_id, 'site_logo' ); } protected function add_site_icon_to_index( WP_REST_Response $response ) { $site_icon_id = get_option( 'site_icon', 0 ); $this->add_image_to_index( $response, $site_icon_id, 'site_icon' ); $response->data['site_icon_url'] = get_site_icon_url(); } protected function add_image_to_index( WP_REST_Response $response, $image_id, $type ) { $response->data[ $type ] = (int) $image_id; if ( $image_id ) { $response->add_link( 'https://api.w.org/featuredmedia', rest_url( rest_get_route_for_post( $image_id ) ), array( 'embeddable' => true, 'type' => $type, ) ); } } public function get_namespace_index( $request ) { $namespace = $request['namespace']; if ( ! isset( $this->namespaces[ $namespace ] ) ) { return new WP_Error( 'rest_invalid_namespace', __( 'The specified namespace could not be found.' ), array( 'status' => 404 ) ); } $routes = $this->namespaces[ $namespace ]; $endpoints = array_intersect_key( $this->get_routes(), $routes ); $data = array( 'namespace' => $namespace, 'routes' => $this->get_data_for_routes( $endpoints, $request['context'] ), ); $response = rest_ensure_response( $data ); $response->add_link( 'up', rest_url( '/' ) ); return apply_filters( 'rest_namespace_index', $response, $request ); } public function get_data_for_routes( $routes, $context = 'view' ) { $available = array(); foreach ( $routes as $route => $callbacks ) { $data = $this->get_data_for_route( $route, $callbacks, $context ); if ( empty( $data ) ) { continue; } $available[ $route ] = apply_filters( 'rest_endpoints_description', $data ); } return apply_filters( 'rest_route_data', $available, $routes ); } public function get_data_for_route( $route, $callbacks, $context = 'view' ) { $data = array( 'namespace' => '', 'methods' => array(), 'endpoints' => array(), ); $allow_batch = false; if ( isset( $this->route_options[ $route ] ) ) { $options = $this->route_options[ $route ]; if ( isset( $options['namespace'] ) ) { $data['namespace'] = $options['namespace']; } $allow_batch = isset( $options['allow_batch'] ) ? $options['allow_batch'] : false; if ( isset( $options['schema'] ) && 'help' === $context ) { $data['schema'] = call_user_func( $options['schema'] ); } } $allowed_schema_keywords = array_flip( rest_get_allowed_schema_keywords() ); $route = preg_replace( '#\(\?P<(\w+?)>.*?\)#', '{$1}', $route ); foreach ( $callbacks as $callback ) { if ( empty( $callback['show_in_index'] ) ) { continue; } $data['methods'] = array_merge( $data['methods'], array_keys( $callback['methods'] ) ); $endpoint_data = array( 'methods' => array_keys( $callback['methods'] ), ); $callback_batch = isset( $callback['allow_batch'] ) ? $callback['allow_batch'] : $allow_batch; if ( $callback_batch ) { $endpoint_data['allow_batch'] = $callback_batch; } if ( isset( $callback['args'] ) ) { $endpoint_data['args'] = array(); foreach ( $callback['args'] as $key => $opts ) { if ( is_string( $opts ) ) { $opts = array( $opts => 0 ); } elseif ( ! is_array( $opts ) ) { $opts = array(); } $arg_data = array_intersect_key( $opts, $allowed_schema_keywords ); $arg_data['required'] = ! empty( $opts['required'] ); $endpoint_data['args'][ $key ] = $arg_data; } } $data['endpoints'][] = $endpoint_data; if ( strpos( $route, '{' ) === false ) { $data['_links'] = array( 'self' => array( array( 'href' => rest_url( $route ), ), ), ); } } if ( empty( $data['methods'] ) ) { return null; } return $data; } protected function get_max_batch_size() { return apply_filters( 'rest_get_max_batch_size', 25 ); } public function serve_batch_request_v1( WP_REST_Request $batch_request ) { $requests = array(); foreach ( $batch_request['requests'] as $args ) { $parsed_url = wp_parse_url( $args['path'] ); if ( false === $parsed_url ) { $requests[] = new WP_Error( 'parse_path_failed', __( 'Could not parse the path.' ), array( 'status' => 400 ) ); continue; } $single_request = new WP_REST_Request( isset( $args['method'] ) ? $args['method'] : 'POST', $parsed_url['path'] ); if ( ! empty( $parsed_url['query'] ) ) { $query_args = null; wp_parse_str( $parsed_url['query'], $query_args ); $single_request->set_query_params( $query_args ); } if ( ! empty( $args['body'] ) ) { $single_request->set_body_params( $args['body'] ); } if ( ! empty( $args['headers'] ) ) { $single_request->set_headers( $args['headers'] ); } $requests[] = $single_request; } $matches = array(); $validation = array(); $has_error = false; foreach ( $requests as $single_request ) { $match = $this->match_request_to_handler( $single_request ); $matches[] = $match; $error = null; if ( is_wp_error( $match ) ) { $error = $match; } if ( ! $error ) { list( $route, $handler ) = $match; if ( isset( $handler['allow_batch'] ) ) { $allow_batch = $handler['allow_batch']; } else { $route_options = $this->get_route_options( $route ); $allow_batch = isset( $route_options['allow_batch'] ) ? $route_options['allow_batch'] : false; } if ( ! is_array( $allow_batch ) || empty( $allow_batch['v1'] ) ) { $error = new WP_Error( 'rest_batch_not_allowed', __( 'The requested route does not support batch requests.' ), array( 'status' => 400 ) ); } } if ( ! $error ) { $check_required = $single_request->has_valid_params(); if ( is_wp_error( $check_required ) ) { $error = $check_required; } } if ( ! $error ) { $check_sanitized = $single_request->sanitize_params(); if ( is_wp_error( $check_sanitized ) ) { $error = $check_sanitized; } } if ( $error ) { $has_error = true; $validation[] = $error; } else { $validation[] = true; } } $responses = array(); if ( $has_error && 'require-all-validate' === $batch_request['validation'] ) { foreach ( $validation as $valid ) { if ( is_wp_error( $valid ) ) { $responses[] = $this->envelope_response( $this->error_to_response( $valid ), false )->get_data(); } else { $responses[] = null; } } return new WP_REST_Response( array( 'failed' => 'validation', 'responses' => $responses, ), WP_Http::MULTI_STATUS ); } foreach ( $requests as $i => $single_request ) { $clean_request = clone $single_request; $clean_request->set_url_params( array() ); $clean_request->set_attributes( array() ); $clean_request->set_default_params( array() ); $result = apply_filters( 'rest_pre_dispatch', null, $this, $clean_request ); if ( empty( $result ) ) { $match = $matches[ $i ]; $error = null; if ( is_wp_error( $validation[ $i ] ) ) { $error = $validation[ $i ]; } if ( is_wp_error( $match ) ) { $result = $this->error_to_response( $match ); } else { list( $route, $handler ) = $match; if ( ! $error && ! is_callable( $handler['callback'] ) ) { $error = new WP_Error( 'rest_invalid_handler', __( 'The handler for the route is invalid' ), array( 'status' => 500 ) ); } $result = $this->respond_to_request( $single_request, $route, $handler, $error ); } } $result = apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $this, $single_request ); $responses[] = $this->envelope_response( $result, false )->get_data(); } return new WP_REST_Response( array( 'responses' => $responses ), WP_Http::MULTI_STATUS ); } protected function set_status( $code ) { status_header( $code ); } public function send_header( $key, $value ) { $value = preg_replace( '/\s+/', ' ', $value ); header( sprintf( '%s: %s', $key, $value ) ); } public function send_headers( $headers ) { foreach ( $headers as $key => $value ) { $this->send_header( $key, $value ); } } public function remove_header( $key ) { header_remove( $key ); } public static function get_raw_data() { global $HTTP_RAW_POST_DATA; if ( ! isset( $HTTP_RAW_POST_DATA ) ) { $HTTP_RAW_POST_DATA = file_get_contents( 'php://input' ); } return $HTTP_RAW_POST_DATA; } public function get_headers( $server ) { $headers = array(); $additional = array( 'CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true, ); foreach ( $server as $key => $value ) { if ( strpos( $key, 'HTTP_' ) === 0 ) { $headers[ substr( $key, 5 ) ] = $value; } elseif ( 'REDIRECT_HTTP_AUTHORIZATION' === $key && empty( $server['HTTP_AUTHORIZATION'] ) ) { $headers['AUTHORIZATION'] = $value; } elseif ( isset( $additional[ $key ] ) ) { $headers[ $key ] = $value; } } return $headers; } } 