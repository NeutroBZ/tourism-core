<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Database leases shared across requests, independent of the object cache. */
class Tourism_Core_Lock {
	private $name;
	private $value;

	public function __construct( $key ) {
		$this->name = '_tourism_core_lock_' . hash( 'sha256', $key );
	}

	public function acquire( $seconds = 120 ) {
		global $wpdb;
		$value = ( time() + $seconds ) . ':' . wp_generate_uuid4();
		$inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $this->name, $value ) );
		if ( 1 !== $inserted ) {
			$old = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $this->name ) );
			if ( ! is_string( $old ) || (int) $old >= time() ) {
				return false;
			}
			if ( 1 !== $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", $value, $this->name, $old ) ) ) {
				return false;
			}
		}
		$this->value = $value;
		return true;
	}

	public function release() {
		global $wpdb;
		if ( null !== $this->value ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $this->name, $this->value ) );
			$this->value = null;
		}
	}

	public static function cleanup() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(SUBSTRING_INDEX(option_value, ':', 1) AS UNSIGNED) < %d", $wpdb->esc_like( '_tourism_core_lock_' ) . '%', time() ) );
	}
}
