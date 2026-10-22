<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Tour_Taxonomies {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_taxonomies' );
	}

	public function register_taxonomies() {
		register_taxonomy( 'tour_tag', array( 'tour' ), array(
			'label' => __( 'Etiquetas de tours', TOURISM_CORE_TD ), 'public' => true,
			'hierarchical' => false, 'show_in_rest' => true, 'show_admin_column' => true,
			'rewrite' => array( 'slug' => 'tour_tag' ),
		) );

		$this->register_taxonomy(
			'tour_category',
			__( 'Categorías de tours', TOURISM_CORE_TD ),
			__( 'Categoría del tour', TOURISM_CORE_TD )
		);

		$this->register_taxonomy(
			'tour_type',
			__( 'Tipos de tour', TOURISM_CORE_TD ),
			__( 'Tipo de tour', TOURISM_CORE_TD )
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

