<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'modules/emails/class-email-templates.php';

class Tourism_Core_Email_Manager {
	/** @var Tourism_Core_Email_Templates */
	private $templates;

	public function __construct() {
		$this->templates = new Tourism_Core_Email_Templates();
	}

	public function send_booking_admin_notice( $booking_id ) {
		$booking = $this->get_booking_payload( $booking_id );
		if ( ! $booking ) {
			return false;
		}

		$to = get_option( 'admin_email' );
		if ( ! $to ) {
			return false;
		}

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		return wp_mail( $to, $this->templates->admin_subject(), $this->templates->admin_body( $booking ), $headers );
	}

	public function send_booking_customer_confirmation( $booking_id ) {
		$booking = $this->get_booking_payload( $booking_id );
		if ( ! $booking || ! is_email( $booking['email'] ) ) {
			return false;
		}

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		return wp_mail( $booking['email'], $this->templates->customer_subject(), $this->templates->customer_body( $booking ), $headers );
	}

	private function get_booking_payload( $booking_id ) {
		$booking_id = (int) $booking_id;
		if ( $booking_id <= 0 || 'booking' !== get_post_type( $booking_id ) ) {
			return null;
		}

		return array(
			'id'      => $booking_id,
			'tour_id' => (int) get_post_meta( $booking_id, 'tourism_tour_id', true ),
			'name'    => (string) get_post_meta( $booking_id, 'tourism_customer_name', true ),
			'email'   => (string) get_post_meta( $booking_id, 'tourism_customer_email', true ),
			'phone'   => (string) get_post_meta( $booking_id, 'tourism_customer_phone', true ),
			'people'  => (int) get_post_meta( $booking_id, 'tourism_people', true ),
			'date'    => (string) get_post_meta( $booking_id, 'tourism_date', true ),
			'message' => (string) get_post_meta( $booking_id, 'tourism_message', true ),
		);
	}
}

