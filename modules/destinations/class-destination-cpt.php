<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Module_Destinations {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_cpt' );
		$taxonomies = new Tourism_Core_Destination_Taxonomies();
		$taxonomies->register($loader);
		$metaboxes = new Tourism_Core_Destination_Metaboxes();
		$metaboxes->register( $loader );
	}

	public function register_cpt() {
		$labels = array(
			'name'               => __( 'Destinations', TOURISM_CORE_TD ),
			'singular_name'      => __( 'Destination', TOURISM_CORE_TD ),
			'add_new_item'       => __( 'Add New Destination', TOURISM_CORE_TD ),
			'edit_item'          => __( 'Edit Destination', TOURISM_CORE_TD ),
			'new_item'           => __( 'New Destination', TOURISM_CORE_TD ),
			'view_item'          => __( 'View Destination', TOURISM_CORE_TD ),
			'search_items'       => __( 'Search Destinations', TOURISM_CORE_TD ),
			'not_found'          => __( 'No destinations found', TOURISM_CORE_TD ),
			'not_found_in_trash' => __( 'No destinations found in Trash', TOURISM_CORE_TD ),
			'menu_name'          => __( 'Destinations', TOURISM_CORE_TD ),
		);

		register_post_type(
			'destination',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => TOURISM_CORE_URL . 'assets/ico/destinations3.svg',
				'supports'     => array( 'title', 'thumbnail' ),
				'rewrite'      => array( 'slug' => 'destinations' ),
			)
		);
	}
}

