<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Booking_Validator {
	public static function max_people() {
		return max( 1, (int) apply_filters( 'tourism_core_booking_max_people', 100 ) );
	}

	/** Validate raw unslashed input before saving any customer information. */
	public static function validate( $raw ) {
		if ( ! is_array( $raw ) ) {
			return new WP_Error( 'fields' );
		}
		foreach ( array( 'tour_id', 'name', 'email', 'phone', 'message', 'people', 'date', 'booking_website' ) as $field ) {
			if ( isset( $raw[ $field ] ) && ! is_scalar( $raw[ $field ] ) ) {
				return new WP_Error( 'fields' );
			}
		}
		if ( '' !== trim( (string) ( $raw['booking_website'] ?? '' ) ) ) {
			return new WP_Error( 'spam' );
		}
		$tour_id = filter_var( $raw['tour_id'] ?? '', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		if ( ! $tour_id || 'tour' !== get_post_type( $tour_id ) || 'publish' !== get_post_status( $tour_id ) ) {
			return new WP_Error( 'tour' );
		}
		$data = array( 'tour_id' => $tour_id );
		foreach ( array( 'name' => 120, 'email' => 254, 'phone' => 50, 'message' => 5000 ) as $field => $limit ) {
			$value = trim( (string) ( $raw[ $field ] ?? '' ) );
			if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value ) ) > $limit ) {
				return new WP_Error( 'length' );
			}
			$data[ $field ] = 'message' === $field ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
		}
		if ( '' === $data['name'] || ! is_email( $data['email'] ) ) {
			return new WP_Error( 'fields' );
		}
		$data['email'] = sanitize_email( $data['email'] );
		$data['people'] = filter_var( $raw['people'] ?? 1, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'max_range' => self::max_people() ) ) );
		if ( false === $data['people'] ) {
			return new WP_Error( 'people' );
		}
		$date = trim( (string) ( $raw['date'] ?? '' ) );
		if ( '' !== $date ) {
			if ( ! preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $date, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) || $date < current_time( 'Y-m-d' ) ) {
				return new WP_Error( 'date' );
			}
		}
		$data['date'] = $date;
		return $data;
	}
}
