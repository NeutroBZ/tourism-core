<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Booking_CPT {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_cpt' );
		$loader->add_action( 'init', $this, 'register_meta' );
	}

	public function register_cpt() {
		$labels = array(
			'name'               => __( 'Bookings', TOURISM_CORE_TD ),
			'singular_name'      => __( 'Booking', TOURISM_CORE_TD ),
			'add_new_item'       => __( 'Add New Booking', TOURISM_CORE_TD ),
			'edit_item'          => __( 'Edit Booking', TOURISM_CORE_TD ),
			'new_item'           => __( 'New Booking', TOURISM_CORE_TD ),
			'view_item'          => __( 'View Booking', TOURISM_CORE_TD ),
			'search_items'       => __( 'Search Bookings', TOURISM_CORE_TD ),
			'not_found'          => __( 'No bookings found', TOURISM_CORE_TD ),
			'not_found_in_trash' => __( 'No bookings found in Trash', TOURISM_CORE_TD ),
			'menu_name'          => __( 'Bookings', TOURISM_CORE_TD ),
		);

		register_post_type(
			'booking',
			array(
				'labels'             => $labels,
				'public'             => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'menu_icon'          => TOURISM_CORE_URL . 'assets/ico/bookings3.svg',
				'supports'           => array( 'title' ),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'exclude_from_search'=> true,
				'show_in_rest'       => false,
			)
		);
	}

	public function register_meta() {
		$this->meta_string( 'booking', 'tourism_customer_name' );
		$this->meta_string( 'booking', 'tourism_customer_email' );
		$this->meta_string( 'booking', 'tourism_customer_phone' );
		$this->meta_string( 'booking', 'tourism_message' );
		$this->meta_int( 'booking', 'tourism_tour_id' );
		$this->meta_int( 'booking', 'tourism_people' );
		$this->meta_string( 'booking', 'tourism_date' ); // YYYY-MM-DD (simple MVP)
	}

	private function meta_string( $post_type, $key ) {
		register_post_meta(
			(string) $post_type,
			(string) $key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	private function meta_int( $post_type, $key ) {
		register_post_meta(
			(string) $post_type,
			(string) $key,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => static function ( $value ) {
					return is_numeric( $value ) ? (int) $value : null;
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}

