<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Booking_Handler {
	/** @var Tourism_Core_Email_Manager */
	private $emails;

	public function __construct( $emails ) {
		$this->emails = $emails;
	}

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'admin_post_nopriv_tourism_core_create_booking', $this, 'handle_create' );
		$loader->add_action( 'admin_post_tourism_core_create_booking', $this, 'handle_create' );
	}

	public function handle_create() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$this->redirect_back( array( 'tc_error' => 'fields' ) );
		}
		$nonce = isset( $_POST['tourism_core_booking_nonce'] ) && is_string( $_POST['tourism_core_booking_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_core_booking_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tourism_core_create_booking' ) ) {
			$this->redirect_back( array( 'tc_error' => 'nonce' ) );
		}

		$result = $this->create_booking( wp_unslash( $_POST ), isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' );
		if ( is_wp_error( $result ) ) {
			$this->redirect_back( array( 'tc_error' => $result->get_error_code() ) );
		}
		$this->redirect_back( array( 'tc_success' => 1 ) );
	}

	/** Return a booking ID or WP_Error. Duplicate submissions reuse the original ID. */
	public function create_booking( $raw, $ip = '' ) {
		if ( ! tourism_core_is_module_enabled( 'bookings' ) || ! tourism_core_is_module_enabled( 'tours' ) ) {
			return new WP_Error( 'tour' );
		}
		$data = Tourism_Core_Booking_Validator::validate( $raw );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$guard = new Tourism_Core_Booking_Guard();
		try {
			$result = $guard->begin( $data, $ip );
			if ( is_wp_error( $result ) || $result > 0 ) {
				return $result;
			}
			$booking_id = wp_insert_post( array(
				'post_type' => 'booking',
				'post_status' => 'booking_pending',
				'post_title' => sprintf( 'Booking - %s - %s', $data['name'], current_time( 'Y-m-d H:i' ) ),
			), true );
			if ( is_wp_error( $booking_id ) || ! $booking_id ) {
				return new WP_Error( 'create' );
			}
			$fields = array( 'tour_id' => 'tourism_tour_id', 'name' => 'tourism_customer_name', 'email' => 'tourism_customer_email', 'phone' => 'tourism_customer_phone', 'message' => 'tourism_message', 'people' => 'tourism_people', 'date' => 'tourism_date' );
			foreach ( $fields as $field => $meta_key ) {
				update_post_meta( $booking_id, $meta_key, $data[ $field ] );
				if ( (string) get_post_meta( $booking_id, $meta_key, true ) !== (string) $data[ $field ] ) {
					wp_delete_post( $booking_id, true );
					return new WP_Error( 'create' );
				}
			}
			$guard->commit( $booking_id );
		} finally {
			$guard->release();
		}
		if ( $this->emails ) {
			$this->emails->send_booking_admin_notice( $booking_id );
			$this->emails->send_booking_customer_confirmation( $booking_id );
		}
		return $booking_id;
	}

	private function redirect_back( $params ) {
		$ref = wp_get_referer();
		if ( ! $ref ) {
			$ref = home_url( '/' );
		}
		$ref = remove_query_arg( array( 'tc_error', 'tc_success' ), $ref );
		$url = add_query_arg( (array) $params, $ref );
		wp_safe_redirect( $url );
		exit;
	}
}

