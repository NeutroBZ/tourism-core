<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'helpers/class-template.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-taxonomies.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-metaboxes.php';

class Tourism_Core_Module_Tours {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_cpt' );
		$loader->add_action( 'init', $this, 'register_meta' );

		$tax = new Tourism_Core_Tour_Taxonomies();
		$tax->register( $loader );

		$meta = new Tourism_Core_Tour_Metaboxes();
		$meta->register( $loader );
	}

	public function register_cpt() {
		$labels = array(
			'name'               => __( 'Tours', TOURISM_CORE_TD ),
			'singular_name'      => __( 'Tour', TOURISM_CORE_TD ),
			'add_new'            => __( 'Add New', TOURISM_CORE_TD ),
			'add_new_item'       => __( 'Add New Tour', TOURISM_CORE_TD ),
			'edit_item'          => __( 'Edit Tour', TOURISM_CORE_TD ),
			'new_item'           => __( 'New Tour', TOURISM_CORE_TD ),
			'view_item'          => __( 'View Tour', TOURISM_CORE_TD ),
			'search_items'       => __( 'Search Tours', TOURISM_CORE_TD ),
			'not_found'          => __( 'No tours found', TOURISM_CORE_TD ),
			'not_found_in_trash' => __( 'No tours found in Trash', TOURISM_CORE_TD ),
			'menu_name'          => __( 'Tours', TOURISM_CORE_TD ),
		);

		register_post_type(
			'tour',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => TOURISM_CORE_URL . 'assets/ico/tours3.svg',
				'supports'     => array( 'title', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'tours' ),
			)
		);
	}

	public function register_meta() {
		register_post_meta(
			'tour',
			'tourism_price_from',
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => static function ( $value ) {
					return is_numeric( $value ) ? (float) $value : null;
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'tour',
			'tourism_duration',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'tour',
			'tourism_destinations',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => static function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					$out = array();
					foreach ( $value as $v ) {
						if ( is_numeric( $v ) && (int) $v > 0 ) {
							$out[] = (int) $v;
						}
					}
					return array_values( array_unique( $out ) );
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}

