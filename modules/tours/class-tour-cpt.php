<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'helpers/class-template.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-taxonomies.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-metaboxes.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-schema.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-schema-metabox.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-editor.php';

class Tourism_Core_Module_Tours {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_cpt' );
		$loader->add_action( 'init', $this, 'register_meta' );

		$tax = new Tourism_Core_Tour_Taxonomies();
		$tax->register( $loader );

		$meta = new Tourism_Core_Tour_Metaboxes();
		$meta->register( $loader );

		$schema = new Tourism_Core_Tour_Schema();
		$schema->register( $loader );
		$schema_meta = new Tourism_Core_Tour_Schema_Metabox();
		$schema_meta->register( $loader );
		$editor = new Tourism_Core_Tour_Editor();
		$editor->register( $loader );
	}

	public function register_cpt() {
		$labels = array(
			'name'               => __( 'Tours', TOURISM_CORE_TD ),
			'singular_name'      => __( 'Tour', TOURISM_CORE_TD ),
			'add_new'            => __( 'Añadir nuevo', TOURISM_CORE_TD ),
			'add_new_item'       => __( 'Añadir tour', TOURISM_CORE_TD ),
			'edit_item'          => __( 'Editar tour', TOURISM_CORE_TD ),
			'new_item'           => __( 'Nuevo tour', TOURISM_CORE_TD ),
			'view_item'          => __( 'Ver tour', TOURISM_CORE_TD ),
			'search_items'       => __( 'Buscar tours', TOURISM_CORE_TD ),
			'not_found'          => __( 'No se encontraron tours', TOURISM_CORE_TD ),
			'not_found_in_trash' => __( 'No hay tours en la papelera', TOURISM_CORE_TD ),
			'menu_name'          => __( 'Tours', TOURISM_CORE_TD ),
		);

		register_post_type(
			'tour',
			array(
				'labels'       => $labels,
				'public'       => true,
				'has_archive'  => true,
				'show_in_rest' => true,
				'menu_icon'    => TOURISM_CORE_BRAND_ICON,
				'supports'     => array( 'title',  'thumbnail', 'revisions' ),
				'rewrite'      => array( 'slug' => 'tours' ),
				'taxonomies' => array( 'tour_category' ),
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

		register_post_meta(
			'tour',
			'tourism_gallery_ids',
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

		register_post_meta(
			'tour',
			'tourism_gallery_images',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => static function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					$out = array();
					foreach ( $value as $row ) {
						if ( ! is_array( $row ) ) {
							continue;
						}
						$id      = isset( $row['id'] ) ? (int) $row['id'] : 0;
						$url     = isset( $row['url'] ) ? esc_url_raw( (string) $row['url'] ) : '';
						$title   = isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '';
						$caption = isset( $row['caption'] ) ? sanitize_textarea_field( (string) $row['caption'] ) : '';
						if ( $id <= 0 && $url === '' ) {
							continue;
						}
						if ( $id > 0 ) {
							$att_url = wp_get_attachment_url( $id );
							if ( $att_url ) {
								$url = $att_url;
							}
						}
						$out[] = array(
							'id'      => $id,
							'url'     => $url,
							'title'   => $title,
							'caption' => $caption,
						);
					}
					return array_values( $out );
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'tour',
			'tourism_faqs',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => static function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					$out = array();
					foreach ( $value as $row ) {
						if ( ! is_array( $row ) ) {
							continue;
						}
						$question = isset( $row['question'] ) ? sanitize_text_field( (string) $row['question'] ) : '';
						$answer   = isset( $row['answer'] ) ? wp_kses_post( (string) $row['answer'] ) : '';
						if ( $question === '' && $answer === '' ) {
							continue;
						}
						$out[] = array(
							'question' => $question,
							'answer'   => $answer,
						);
					}
					return array_values( $out );
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'tour',
			'tourism_includes',
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
						if ( is_string( $v ) ) {
							$t = sanitize_text_field( $v );
							if ( $t !== '' ) {
								$out[] = $t;
							}
						}
					}
					return array_values( $out );
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'tour',
			'tourism_excludes',
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
						if ( is_string( $v ) ) {
							$t = sanitize_text_field( $v );
							if ( $t !== '' ) {
								$out[] = $t;
							}
						}
					}
					return array_values( $out );
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'tour',
			'tourism_bring_items',
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => static function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					$out = array();
					foreach ( $value as $row ) {
						if ( ! is_array( $row ) ) {
							continue;
						}
						$id          = isset( $row['id'] ) ? (int) $row['id'] : 0;
						$url         = isset( $row['url'] ) ? esc_url_raw( (string) $row['url'] ) : '';
						$title       = isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '';
						$description = isset( $row['description'] ) ? sanitize_textarea_field( (string) $row['description'] ) : '';
						if ( $id <= 0 && $url === '' && $title === '' && $description === '' ) {
							continue;
						}
						if ( $id > 0 ) {
							$att_url = wp_get_attachment_url( $id );
							if ( $att_url ) {
								$url = $att_url;
							}
						}
						$out[] = array(
							'id'          => $id,
							'url'         => $url,
							'title'       => $title,
							'description' => $description,
						);
					}
					return array_values( $out );
				},
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		$string_metas = array(
			'tourism_itinerary',
			'tourism_things_to_carry',
			'tourism_reviews',
			'tourism_route',
		);

		foreach ( $string_metas as $meta_key ) {
			register_post_meta(
				'tour',
				$meta_key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}

