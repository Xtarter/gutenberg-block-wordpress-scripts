<?php
 class WP_Filesystem_Base { public $verbose = false; public $cache = array(); public $method = ''; public $errors = null; public $options = array(); public function abspath() { $folder = $this->find_folder( ABSPATH ); if ( ! $folder && $this->is_dir( '/' . WPINC ) ) { $folder = '/'; } return $folder; } public function wp_content_dir() { return $this->find_folder( WP_CONTENT_DIR ); } public function wp_plugins_dir() { return $this->find_folder( WP_PLUGIN_DIR ); } public function wp_themes_dir( $theme = false ) { $theme_root = get_theme_root( $theme ); if ( '/themes' === $theme_root || ! is_dir( $theme_root ) ) { $theme_root = WP_CONTENT_DIR . $theme_root; } return $this->find_folder( $theme_root ); } public function wp_lang_dir() { return $this->find_folder( WP_LANG_DIR ); } public function find_base_dir( $base = '.', $verbose = false ) { _deprecated_function( __FUNCTION__, '2.7.0', 'WP_Filesystem_Base::abspath() or WP_Filesystem_Base::wp_*_dir()' ); $this->verbose = $verbose; return $this->abspath(); } public function get_base_dir( $base = '.', $verbose = false ) { _deprecated_function( __FUNCTION__, '2.7.0', 'WP_Filesystem_Base::abspath() or WP_Filesystem_Base::wp_*_dir()' ); $this->verbose = $verbose; return $this->abspath(); } public function find_folder( $folder ) { if ( isset( $this->cache[ $folder ] ) ) { return $this->cache[ $folder ]; } if ( stripos( $this->method, 'ftp' ) !== false ) { $constant_overrides = array( 'FTP_BASE' => ABSPATH, 'FTP_CONTENT_DIR' => WP_CONTENT_DIR, 'FTP_PLUGIN_DIR' => WP_PLUGIN_DIR, 'FTP_LANG_DIR' => WP_LANG_DIR, ); foreach ( $constant_overrides as $constant => $dir ) { if ( ! defined( $constant ) ) { continue; } if ( $folder === $dir ) { return trailingslashit( constant( $constant ) ); } } foreach ( $constant_overrides as $constant => $dir ) { if ( ! defined( $constant ) ) { continue; } if ( 0 === stripos( $folder, $dir ) ) { $potential_folder = preg_replace( '#^' . preg_quote( $dir, '#' ) . '/#i', trailingslashit( constant( $constant ) ), $folder ); $potential_folder = trailingslashit( $potential_folder ); if ( $this->is_dir( $potential_folder ) ) { $this->cache[ $folder ] = $potential_folder; return $potential_folder; } } } } elseif ( 'direct' === $this->method ) { $folder = str_replace( '\\', '/', $folder ); return trailingslashit( $folder ); } $folder = preg_replace( '|^([a-z]{1}):|i', '', $folder ); $folder = str_replace( '\\', '/', $folder ); if ( isset( $this->cache[ $folder ] ) ) { return $this->cache[ $folder ]; } if ( $this->exists( $folder ) ) { $folder = trailingslashit( $folder ); $this->cache[ $folder ] = $folder; return $folder; } $return = $this->search_for_folder( $folder ); if ( $return ) { $this->cache[ $folder ] = $return; } return $return; } public function search_for_folder( $folder, $base = '.', $loop = false ) { if ( empty( $base ) || '.' === $base ) { $base = trailingslashit( $this->cwd() ); } $folder = untrailingslashit( $folder ); if ( $this->verbose ) { printf( "\n" . __( 'Looking for %1$s in %2$s' ) . "<br />\n", $folder, $base ); } $folder_parts = explode( '/', $folder ); $folder_part_keys = array_keys( $folder_parts ); $last_index = array_pop( $folder_part_keys ); $last_path = $folder_parts[ $last_index ]; $files = $this->dirlist( $base ); foreach ( $folder_parts as $index => $key ) { if ( $index === $last_index ) { continue; } if ( isset( $files[ $key ] ) ) { $newdir = trailingslashit( path_join( $base, $key ) ); if ( $this->verbose ) { printf( "\n" . __( 'Changing to %s' ) . "<br />\n", $newdir ); } $newfolder = implode( '/', array_slice( $folder_parts, $index + 1 ) ); $ret = $this->search_for_folder( $newfolder, $newdir, $loop ); if ( $ret ) { return $ret; } } } if ( isset( $files[ $last_path ] ) ) { if ( $this->verbose ) { printf( "\n" . __( 'Found %s' ) . "<br />\n", $base . $last_path ); } return trailingslashit( $base . $last_path ); } if ( $loop || '/' === $base ) { return false; } return $this->search_for_folder( $folder, '/', true ); } public function gethchmod( $file ) { $perms = intval( $this->getchmod( $file ), 8 ); if ( ( $perms & 0xC000 ) === 0xC000 ) { $info = 's'; } elseif ( ( $perms & 0xA000 ) === 0xA000 ) { $info = 'l'; } elseif ( ( $perms & 0x8000 ) === 0x8000 ) { $info = '-'; } elseif ( ( $perms & 0x6000 ) === 0x6000 ) { $info = 'b'; } elseif ( ( $perms & 0x4000 ) === 0x4000 ) { $info = 'd'; } elseif ( ( $perms & 0x2000 ) === 0x2000 ) { $info = 'c'; } elseif ( ( $perms & 0x1000 ) === 0x1000 ) { $info = 'p'; } else { $info = 'u'; } $info .= ( ( $perms & 0x0100 ) ? 'r' : '-' ); $info .= ( ( $perms & 0x0080 ) ? 'w' : '-' ); $info .= ( ( $perms & 0x0040 ) ? ( ( $perms & 0x0800 ) ? 's' : 'x' ) : ( ( $perms & 0x0800 ) ? 'S' : '-' ) ); $info .= ( ( $perms & 0x0020 ) ? 'r' : '-' ); $info .= ( ( $perms & 0x0010 ) ? 'w' : '-' ); $info .= ( ( $perms & 0x0008 ) ? ( ( $perms & 0x0400 ) ? 's' : 'x' ) : ( ( $perms & 0x0400 ) ? 'S' : '-' ) ); $info .= ( ( $perms & 0x0004 ) ? 'r' : '-' ); $info .= ( ( $perms & 0x0002 ) ? 'w' : '-' ); $info .= ( ( $perms & 0x0001 ) ? ( ( $perms & 0x0200 ) ? 't' : 'x' ) : ( ( $perms & 0x0200 ) ? 'T' : '-' ) ); return $info; } public function getchmod( $file ) { return '777'; } public function getnumchmodfromh( $mode ) { $realmode = ''; $legal = array( '', 'w', 'r', 'x', '-' ); $attarray = preg_split( '//', $mode ); for ( $i = 0, $c = count( $attarray ); $i < $c; $i++ ) { $key = array_search( $attarray[ $i ], $legal, true ); if ( $key ) { $realmode .= $legal[ $key ]; } } $mode = str_pad( $realmode, 10, '-', STR_PAD_LEFT ); $trans = array( '-' => '0', 'r' => '4', 'w' => '2', 'x' => '1', ); $mode = strtr( $mode, $trans ); $newmode = $mode[0]; $newmode .= $mode[1] + $mode[2] + $mode[3]; $newmode .= $mode[4] + $mode[5] + $mode[6]; $newmode .= $mode[7] + $mode[8] + $mode[9]; return $newmode; } public function is_binary( $text ) { return (bool) preg_match( '|[^\x20-\x7E]|', $text ); } public function chown( $file, $owner, $recursive = false ) { return false; } public function connect() { return true; } public function get_contents( $file ) { return false; } public function get_contents_array( $file ) { return false; } public function put_contents( $file, $contents, $mode = false ) { return false; } public function cwd() { return false; } public function chdir( $dir ) { return false; } public function chgrp( $file, $group, $recursive = false ) { return false; } public function chmod( $file, $mode = false, $recursive = false ) { return false; } public function owner( $file ) { return false; } public function group( $file ) { return false; } public function copy( $source, $destination, $overwrite = false, $mode = false ) { return false; } public function move( $source, $destination, $overwrite = false ) { return false; } public function delete( $file, $recursive = false, $type = false ) { return false; } public function exists( $path ) { return false; } public function is_file( $file ) { return false; } public function is_dir( $path ) { return false; } public function is_readable( $file ) { return false; } public function is_writable( $path ) { return false; } public function atime( $file ) { return false; } public function mtime( $file ) { return false; } public function size( $file ) { return false; } public function touch( $file, $time = 0, $atime = 0 ) { return false; } public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) { return false; } public function rmdir( $path, $recursive = false ) { return false; } public function dirlist( $path, $include_hidden = true, $recursive = false ) { return false; } } 