<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Tour_Taxonomies {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_taxonomies' );
	}

	public function register_taxonomies() {
		$this->register_taxonomy(
			'tour_type',
			__( 'Tour Types', TOURISM_CORE_TD ),
			__( 'Tour Type', TOURISM_CORE_TD )
		);
	}

	private function register_taxonomy( $taxonomy, $plural, $singular ) {
		register_taxonomy(
			(string) $taxonomy,
			array( 'tour' ),
			array(
				'labels'       => array(
					'name'          => $plural,
					'singular_name' => $singular,
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => true,
				'show_admin_column' => true,
				'rewrite'      => array( 'slug' => $taxonomy ),
			)
		);
	}
}

