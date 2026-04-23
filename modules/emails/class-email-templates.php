<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Email_Templates {
	public function admin_subject() {
		return __( 'New booking request', TOURISM_CORE_TD );
	}

	public function customer_subject() {
		return __( 'We received your booking request', TOURISM_CORE_TD );
	}

	public function admin_body( $booking ) {
		$tour_title = get_the_title( (int) $booking['tour_id'] );

		return implode(
			"\n",
			array(
				__( 'A new booking request was received:', TOURISM_CORE_TD ),
				'',
				sprintf( __( 'Tour: %s', TOURISM_CORE_TD ), $tour_title ),
				sprintf( __( 'Name: %s', TOURISM_CORE_TD ), $booking['name'] ),
				sprintf( __( 'Email: %s', TOURISM_CORE_TD ), $booking['email'] ),
				sprintf( __( 'Phone: %s', TOURISM_CORE_TD ), $booking['phone'] ),
				sprintf( __( 'People: %s', TOURISM_CORE_TD ), $booking['people'] ),
				sprintf( __( 'Date: %s', TOURISM_CORE_TD ), $booking['date'] ),
				'',
				__( 'Message:', TOURISM_CORE_TD ),
				$booking['message'],
			)
		);
	}

	public function customer_body( $booking ) {
		$tour_title = get_the_title( (int) $booking['tour_id'] );

		return implode(
			"\n",
			array(
				sprintf( __( 'Hi %s,', TOURISM_CORE_TD ), $booking['name'] ),
				'',
				__( 'Thanks for your booking request. We will get back to you shortly.', TOURISM_CORE_TD ),
				'',
				sprintf( __( 'Tour: %s', TOURISM_CORE_TD ), $tour_title ),
				sprintf( __( 'People: %s', TOURISM_CORE_TD ), $booking['people'] ),
				sprintf( __( 'Date: %s', TOURISM_CORE_TD ), $booking['date'] ),
			)
		);
	}
}

