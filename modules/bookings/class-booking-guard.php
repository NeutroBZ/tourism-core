<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'includes/class-lock.php';

class Tourism_Core_Booking_Guard {
	const WINDOW = 600;
	private $locks = array();
	private $rate_keys = array();
	private $fingerprint;

	/** Returns an existing booking ID, zero for a new request, or WP_Error. */
	public function begin( $data, $ip ) {
		$this->fingerprint = 'tc_booking_duplicate_' . wp_hash( wp_json_encode( $data ) );
		$this->rate_keys = array( 'tc_booking_email_' . wp_hash( strtolower( $data['email'] ) ) => 3 );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$this->rate_keys[ 'tc_booking_ip_' . wp_hash( $ip ) ] = 5;
		}
		$keys = array_merge( array( $this->fingerprint ), array_keys( $this->rate_keys ) );
		sort( $keys );
		foreach ( $keys as $key ) {
			$lock = new Tourism_Core_Lock( $key );
			if ( ! $lock->acquire() ) {
				$this->release();
				return new WP_Error( 'busy' );
			}
			$this->locks[] = $lock;
		}
		$existing = (int) get_transient( $this->fingerprint );
		if ( $existing && 'booking' === get_post_type( $existing ) && 'trash' !== get_post_status( $existing ) ) {
			return $existing;
		}
		foreach ( $this->rate_keys as $key => $limit ) {
			$rate = get_transient( $key );
			if ( is_array( $rate ) && $rate['expires'] > time() && $rate['count'] >= $limit ) {
				$this->release();
				return new WP_Error( 'rate' );
			}
		}
		return 0;
	}

	public function commit( $booking_id ) {
		set_transient( $this->fingerprint, $booking_id, self::WINDOW );
		foreach ( $this->rate_keys as $key => $limit ) {
			$rate = get_transient( $key );
			if ( ! is_array( $rate ) || $rate['expires'] <= time() ) {
				$rate = array( 'count' => 0, 'expires' => time() + self::WINDOW );
			}
			++$rate['count'];
			set_transient( $key, $rate, max( 1, $rate['expires'] - time() ) );
		}
	}

	public function release() {
		foreach ( $this->locks as $lock ) {
			$lock->release();
		}
		$this->locks = array();
	}
}
