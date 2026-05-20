<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Module_Registry {
	/**
	 * @return array<string, array{label: string, description: string, default: bool}>
	 */
	public static function all() {
		return array(
			'tours'        => array(
				'label'       => __( 'Tours', TOURISM_CORE_TD ),
				'description' => __( 'Tour custom post type, taxonomies, and tour metaboxes.', TOURISM_CORE_TD ),
				'default'     => true,
			),
			'destinations' => array(
				'label'       => __( 'Destinations', TOURISM_CORE_TD ),
				'description' => __( 'Destination custom post type, location taxonomies, and destination metaboxes.', TOURISM_CORE_TD ),
				'default'     => true,
			),
			'bookings'     => array(
				'label'       => __( 'Bookings', TOURISM_CORE_TD ),
				'description' => __( 'Booking requests, booking admin, and notification emails.', TOURISM_CORE_TD ),
				'default'     => true,
			),
		);
	}

	/**
	 * @return array<string, bool>
	 */
	public static function default_modules() {
		$modules = array();
		foreach ( self::all() as $key => $def ) {
			$modules[ $key ] = ! empty( $def['default'] );
		}
		return $modules;
	}

	/**
	 * @param array<string, mixed> $raw
	 * @return array<string, bool>
	 */
	public static function sanitize_modules( $raw ) {
		$sanitized = self::default_modules();
		if ( ! is_array( $raw ) ) {
			return $sanitized;
		}

		foreach ( array_keys( $sanitized ) as $key ) {
			$sanitized[ $key ] = ! empty( $raw[ $key ] );
		}

		return $sanitized;
	}
}
