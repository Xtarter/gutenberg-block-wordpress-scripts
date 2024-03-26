<?php
 function sqlite_make_db_sqlite() { include_once ABSPATH . 'wp-admin/includes/schema.php'; $table_schemas = wp_get_db_schema(); $queries = explode( ';', $table_schemas ); try { $pdo = new PDO( 'sqlite:' . FQDB, null, null, array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ) ); } catch ( PDOException $err ) { $err_data = $err->errorInfo; $message = 'Database connection error!<br />'; $message .= sprintf( 'Error message is: %s', $err_data[2] ); wp_die( $message, 'Database Error!' ); } $translator = new WP_SQLite_Translator( $pdo, $GLOBALS['table_prefix'] ); $query = null; try { $translator->begin_transaction(); foreach ( $queries as $query ) { $query = trim( $query ); if ( empty( $query ) ) { continue; } $result = $translator->query( $query ); if ( false === $result ) { throw new PDOException( $translator->get_error_message() ); } } $translator->commit(); } catch ( PDOException $err ) { $err_data = $err->errorInfo; $err_code = $err_data[1]; $translator->rollback(); $message = sprintf( 'Error occurred while creating tables or indexes...<br />Query was: %s<br />', var_export( $query, true ) ); $message .= sprintf( 'Error message is: %s', $err_data[2] ); wp_die( $message, 'Database Error!' ); } if ( defined( 'SQLITE_DEBUG_CROSSCHECK' ) && SQLITE_DEBUG_CROSSCHECK ) { $host = DB_HOST; $port = 3306; if ( str_contains( $host, ':' ) ) { $host_parts = explode( ':', $host ); $host = $host_parts[0]; $port = $host_parts[1]; } $dsn = 'mysql:host=' . $host . '; port=' . $port . '; dbname=' . DB_NAME; $pdo_mysql = new PDO( $dsn, DB_USER, DB_PASSWORD, array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ) ); $pdo_mysql->query( 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' ); $pdo_mysql->query( 'SET time_zone = "+00:00";' ); foreach ( $queries as $query ) { $query = trim( $query ); if ( empty( $query ) ) { continue; } try { $pdo_mysql->beginTransaction(); $pdo_mysql->query( $query ); } catch ( PDOException $err ) { $err_data = $err->errorInfo; $err_code = $err_data[1]; if ( 5 == $err_code || 6 == $err_code ) { $pdo_mysql->commit(); } else { $pdo_mysql->rollBack(); $message = sprintf( 'Error occurred while creating tables or indexes...<br />Query was: %s<br />', var_export( $query, true ) ); $message .= sprintf( 'Error message is: %s', $err_data[2] ); wp_die( $message, 'Database Error!' ); } } } } $pdo = null; return true; } function wp_install( $blog_title, $user_name, $user_email, $is_public, $deprecated = '', $user_password = '', $language = '' ) { if ( ! empty( $deprecated ) ) { _deprecated_argument( __FUNCTION__, '2.6.0' ); } wp_check_mysql_version(); wp_cache_flush(); sqlite_make_db_sqlite(); populate_options(); populate_roles(); update_option( 'blogname', $blog_title ); update_option( 'admin_email', $user_email ); update_option( 'blog_public', $is_public ); update_option( 'fresh_site', 1 ); if ( $language ) { update_option( 'WPLANG', $language ); } $guessurl = wp_guess_url(); update_option( 'siteurl', $guessurl ); if ( ! $is_public ) { update_option( 'default_pingback_flag', 0 ); } $user_id = username_exists( $user_name ); $user_password = trim( $user_password ); $email_password = false; $user_created = false; if ( ! $user_id && empty( $user_password ) ) { $user_password = wp_generate_password( 12, false ); $message = __( '<strong><em>Note that password</em></strong> carefully! It is a <em>random</em> password that was generated just for you.', 'sqlite-database-integration' ); $user_id = wp_create_user( $user_name, $user_password, $user_email ); update_user_meta( $user_id, 'default_password_nag', true ); $email_password = true; $user_created = true; } elseif ( ! $user_id ) { $message = '<em>' . __( 'Your chosen password.', 'sqlite-database-integration' ) . '</em>'; $user_id = wp_create_user( $user_name, $user_password, $user_email ); $user_created = true; } else { $message = __( 'User already exists. Password inherited.', 'sqlite-database-integration' ); } $user = new WP_User( $user_id ); $user->set_role( 'administrator' ); if ( $user_created ) { $user->user_url = $guessurl; wp_update_user( $user ); } wp_install_defaults( $user_id ); wp_install_maybe_enable_pretty_permalinks(); flush_rewrite_rules(); wp_new_blog_notification( $blog_title, $guessurl, $user_id, ( $email_password ? $user_password : __( 'The password you chose during installation.', 'sqlite-database-integration' ) ) ); wp_cache_flush(); do_action( 'wp_install', $user ); return array( 'url' => $guessurl, 'user_id' => $user_id, 'password' => $user_password, 'password_message' => $message, ); } 