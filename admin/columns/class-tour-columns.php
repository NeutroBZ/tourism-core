<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Admin_Tour_Columns {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_filter( 'manage_tour_posts_columns', $this, 'columns' );
		$loader->add_action( 'manage_tour_posts_custom_column', $this, 'render', 10, 2 );
	}

	public function columns( $columns ) {
		$new = array();
		foreach ( (array) $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['tourism_destinations'] = __( 'Destinations', TOURISM_CORE_TD );
			}
		}
		return $new;
	}

	public function render( $column, $post_id ) {
		if ( 'tourism_destinations' !== (string) $column ) {
			return;
		}

		$ids = get_post_meta( $post_id, 'tourism_destinations', true );
		if ( ! is_array( $ids ) || empty( $ids ) ) {
			echo '&#8212;';
			return;
		}

		$links = array();
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id <= 0 || 'destination' !== get_post_type( $id ) ) {
				continue;
			}
			$title = get_the_title( $id );
			$edit  = get_edit_post_link( $id );
			if ( $edit ) {
				$links[] = '<a href="' . esc_url( $edit ) . '">' . esc_html( $title ) . '</a>';
			} else {
				$links[] = esc_html( $title );
			}
		}

		if ( empty( $links ) ) {
			echo '&#8212;';
			return;
		}

		echo implode( ', ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

