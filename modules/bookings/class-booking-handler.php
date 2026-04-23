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
		$nonce = isset( $_POST['tourism_core_booking_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_core_booking_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tourism_core_create_booking' ) ) {
			$this->redirect_back( array( 'tc_error' => 'nonce' ) );
		}

		$tour_id = isset( $_POST['tour_id'] ) ? (int) $_POST['tour_id'] : 0;
		if ( $tour_id <= 0 || 'tour' !== get_post_type( $tour_id ) ) {
			$this->redirect_back( array( 'tc_error' => 'tour' ) );
		}

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$msg   = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$people = isset( $_POST['people'] ) ? (int) $_POST['people'] : 1;
		$date   = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

		if ( '' === $name || '' === $email || ! is_email( $email ) ) {
			$this->redirect_back( array( 'tc_error' => 'fields' ) );
		}

		if ( $people < 1 ) {
			$people = 1;
		}

		$booking_id = wp_insert_post(
			array(
				'post_type'   => 'booking',
				'post_status' => 'booking_pending',
				'post_title'  => sprintf( 'Booking - %s - %s', $name, current_time( 'Y-m-d H:i' ) ),
			),
			true
		);

		if ( is_wp_error( $booking_id ) ) {
			$this->redirect_back( array( 'tc_error' => 'create' ) );
		}

		update_post_meta( $booking_id, 'tourism_tour_id', $tour_id );
		update_post_meta( $booking_id, 'tourism_customer_name', $name );
		update_post_meta( $booking_id, 'tourism_customer_email', $email );
		update_post_meta( $booking_id, 'tourism_customer_phone', $phone );
		update_post_meta( $booking_id, 'tourism_message', $msg );
		update_post_meta( $booking_id, 'tourism_people', $people );
		update_post_meta( $booking_id, 'tourism_date', $date );

		if ( $this->emails ) {
			$this->emails->send_booking_admin_notice( $booking_id );
			$this->emails->send_booking_customer_confirmation( $booking_id );
		}

		$this->redirect_back( array( 'tc_success' => 1 ) );
	}

	private function redirect_back( $params ) {
		$ref = wp_get_referer();
		if ( ! $ref ) {
			$ref = home_url( '/' );
		}
		$url = add_query_arg( (array) $params, $ref );
		wp_safe_redirect( $url );
		exit;
	}
}

