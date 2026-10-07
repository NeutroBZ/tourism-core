<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Frontend_Shortcodes {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_shortcodes' );
	}

	public function register_shortcodes() {
		add_shortcode( 'tour_list', array( $this, 'tour_list' ) );
		add_shortcode( 'booking_form', array( $this, 'booking_form' ) );
	}

	public function tour_list( $atts ) {
		if ( function_exists( 'tourism_core_is_module_enabled' ) && ! tourism_core_is_module_enabled( 'tours' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'posts_per_page' => 6,
				'limit'         => '',
				'category'      => '',
				'tour_type'     => '',
				'columns'       => 3,
				'fields'        => 'image,title,duration,price',
				'order'          => 'DESC',
				'orderby'        => 'date',
			),
			(array) $atts,
			'tour_list'
		);

		if ( '' === $atts['limit'] ) { unset( $atts['limit'] ); }
		return tourism_core_render_tours( $atts );
	}

	public function booking_form( $atts ) {
		if ( function_exists( 'tourism_core_is_module_enabled' ) && ! tourism_core_is_module_enabled( 'bookings' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'tour_id' => 0,
			),
			(array) $atts,
			'booking_form'
		);

		$tour_id = (int) $atts['tour_id'];
		if ( $tour_id <= 0 ) {
			$tour_id = get_the_ID();
		}

		if ( $tour_id <= 0 || 'tour' !== get_post_type( $tour_id ) || 'publish' !== get_post_status( $tour_id ) ) {
			return '';
		}

		return Tourism_Core_Template::render(
			'booking-form.php',
			array(
				'tour_id' => $tour_id,
			)
		);
	}
}

