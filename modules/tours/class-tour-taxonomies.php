<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Tour_Taxonomies {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_taxonomies' );
		$loader->add_action( 'tour_category_add_form_fields', $this, 'image_add_field' );
		$loader->add_action( 'tour_category_edit_form_fields', $this, 'image_edit_field' );
		$loader->add_action( 'created_tour_category', $this, 'save_image' );
		$loader->add_action( 'edited_tour_category', $this, 'save_image' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_image_assets' );
	}

	public function enqueue_image_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'tour_category' !== $screen->taxonomy ) return;
		wp_enqueue_media();
		wp_enqueue_script( 'tourism-category-image', TOURISM_CORE_URL . 'assets/js/category-image.js', array( 'jquery', 'media-views' ), (string) filemtime( TOURISM_CORE_PATH . 'assets/js/category-image.js' ), true );
	}

	public function image_add_field() {
		echo '<div class="form-field"><label>' . esc_html__( 'Imagen de la categoría', TOURISM_CORE_TD ) . '</label>';
		$this->image_field( 0 );
		echo '</div>';
	}

	public function image_edit_field( $term ) {
		echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Imagen de la categoría', TOURISM_CORE_TD ) . '</th><td>';
		$this->image_field( (int) get_term_meta( $term->term_id, 'travel_category_image_id', true ) );
		echo '</td></tr>';
	}

	private function image_field( $image_id ) {
		wp_nonce_field( 'tourism_category_image', 'tourism_category_image_nonce' );
		echo '<div class="tourism-category-image"><input type="hidden" name="travel_category_image_id" value="' . esc_attr( $image_id ) . '"><div class="tourism-category-image-preview">';
		if ( $image_id ) echo wp_get_attachment_image( $image_id, 'thumbnail' );
		echo '</div><p><button type="button" class="button tourism-category-image-select">' . esc_html__( 'Seleccionar imagen', TOURISM_CORE_TD ) . '</button> <button type="button" class="button tourism-category-image-remove"' . ( $image_id ? '' : ' hidden' ) . '>' . esc_html__( 'Quitar imagen', TOURISM_CORE_TD ) . '</button></p></div>';
	}

	public function save_image( $term_id ) {
		if ( ! isset( $_POST['tourism_category_image_nonce'], $_POST['travel_category_image_id'] ) || ! is_scalar( $_POST['tourism_category_image_nonce'] ) || ! is_scalar( $_POST['travel_category_image_id'] ) ) return;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tourism_category_image_nonce'] ) ), 'tourism_category_image' ) || ! current_user_can( 'edit_term', $term_id ) ) return;
		$image_id = absint( $_POST['travel_category_image_id'] );
		if ( $image_id && ! wp_attachment_is_image( $image_id ) ) return;
		if ( $image_id ) update_term_meta( $term_id, 'travel_category_image_id', $image_id );
		else delete_term_meta( $term_id, 'travel_category_image_id' );
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

