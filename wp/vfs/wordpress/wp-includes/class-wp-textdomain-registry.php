<?php
 class WP_Textdomain_Registry { protected $all = array(); protected $current = array(); protected $custom_paths = array(); protected $cached_mo_files; public function get( $domain, $locale ) { if ( isset( $this->all[ $domain ][ $locale ] ) ) { return $this->all[ $domain ][ $locale ]; } return $this->get_path_from_lang_dir( $domain, $locale ); } public function has( $domain ) { return ! empty( $this->current[ $domain ] ) || empty( $this->all[ $domain ] ); } public function set( $domain, $locale, $path ) { $this->all[ $domain ][ $locale ] = $path ? trailingslashit( $path ) : false; $this->current[ $domain ] = $this->all[ $domain ][ $locale ]; } public function set_custom_path( $domain, $path ) { $this->custom_paths[ $domain ] = untrailingslashit( $path ); } private function get_path_from_lang_dir( $domain, $locale ) { $locations = array( WP_LANG_DIR . '/plugins', WP_LANG_DIR . '/themes', ); if ( isset( $this->custom_paths[ $domain ] ) ) { $locations[] = $this->custom_paths[ $domain ]; } $mofile = "$domain-$locale.mo"; foreach ( $locations as $location ) { if ( ! isset( $this->cached_mo_files[ $location ] ) ) { $this->set_cached_mo_files( $location ); } $path = $location . '/' . $mofile; if ( in_array( $path, $this->cached_mo_files[ $location ], true ) ) { $this->set( $domain, $locale, $location ); return trailingslashit( $location ); } } if ( 'en_US' !== $locale && isset( $this->custom_paths[ $domain ] ) ) { $path = trailingslashit( $this->custom_paths[ $domain ] ); $this->set( $domain, $locale, $path ); return $path; } $this->set( $domain, $locale, false ); return false; } private function set_cached_mo_files( $path ) { $this->cached_mo_files[ $path ] = array(); $mo_files = glob( $path . '/*.mo' ); if ( $mo_files ) { $this->cached_mo_files[ $path ] = $mo_files; } } } 