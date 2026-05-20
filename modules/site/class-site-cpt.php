<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Site_CPT {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_cpt' );
	}

	public function register_cpt() {
		$labels = array(
			'name'          => __( 'Site configuration', TOURISM_CORE_TD ),
			'singular_name' => __( 'Site configuration', TOURISM_CORE_TD ),
			'edit_item'     => __( 'Tourism Core settings', TOURISM_CORE_TD ),
		);

		register_post_type(
			'tourism_site',
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
			)
		);
	}
}
