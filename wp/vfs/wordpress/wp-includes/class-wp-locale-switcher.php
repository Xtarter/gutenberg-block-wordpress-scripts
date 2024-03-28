<?php
 class WP_Locale_Switcher { private $locales = array(); private $original_locale; private $available_languages = array(); public function __construct() { $this->original_locale = determine_locale(); $this->available_languages = array_merge( array( 'en_US' ), get_available_languages() ); } public function init() { add_filter( 'locale', array( $this, 'filter_locale' ) ); } public function switch_to_locale( $locale ) { $current_locale = determine_locale(); if ( $current_locale === $locale ) { return false; } if ( ! in_array( $locale, $this->available_languages, true ) ) { return false; } $this->locales[] = $locale; $this->change_locale( $locale ); do_action( 'switch_locale', $locale ); return true; } public function restore_previous_locale() { $previous_locale = array_pop( $this->locales ); if ( null === $previous_locale ) { return false; } $locale = end( $this->locales ); if ( ! $locale ) { $locale = $this->original_locale; } $this->change_locale( $locale ); do_action( 'restore_previous_locale', $locale, $previous_locale ); return $locale; } public function restore_current_locale() { if ( empty( $this->locales ) ) { return false; } $this->locales = array( $this->original_locale ); return $this->restore_previous_locale(); } public function is_switched() { return ! empty( $this->locales ); } public function filter_locale( $locale ) { $switched_locale = end( $this->locales ); if ( $switched_locale ) { return $switched_locale; } return $locale; } private function load_translations( $locale ) { global $l10n; $domains = $l10n ? array_keys( $l10n ) : array(); load_default_textdomain( $locale ); foreach ( $domains as $domain ) { if ( 'default' === $domain ) { continue; } unload_textdomain( $domain, true ); get_translations_for_domain( $domain ); } } private function change_locale( $locale ) { global $wp_locale; $this->load_translations( $locale ); $wp_locale = new WP_Locale(); do_action( 'change_locale', $locale ); } } 