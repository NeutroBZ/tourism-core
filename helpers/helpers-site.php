<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return int
 */
function tourism_core_get_site_post_id() {
	return Tourism_Core_Config::instance()->get_site_post_id();
}

/**
 * @param string $module
 * @return bool
 */
function tourism_core_is_module_enabled( $module ) {
	return Tourism_Core_Config::instance()->is_module_enabled( $module );
}

/**
 * @param string $key Optional agency field key.
 * @return array<string, string>|string
 */
function tourism_core_get_agency( $key = '' ) {
	$config = Tourism_Core_Config::instance();
	if ( '' === $key ) {
		return $config->get_agency();
	}
	return $config->get_agency_field( $key );
}

/**
 * Admin URL for the site configuration screen.
 *
 * @return string
 */
function tourism_core_get_site_settings_url() {
	$post_id = tourism_core_get_site_post_id();
	if ( $post_id <= 0 ) {
		return '';
	}
	return get_edit_post_link( $post_id, 'raw' );
}
