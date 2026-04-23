<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Admin_Booking_Columns {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_filter( 'manage_booking_posts_columns', $this, 'columns' );
		$loader->add_action( 'manage_booking_posts_custom_column', $this, 'render', 10, 2 );
		$loader->add_filter( 'manage_edit-booking_sortable_columns', $this, 'sortable' );
		$loader->add_action( 'pre_get_posts', $this, 'sort_query' );
	}

	public function columns( $columns ) {
		$new = array();
		foreach ( (array) $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['tourism_tour']   = __( 'Tour', TOURISM_CORE_TD );
				$new['tourism_date']   = __( 'Date', TOURISM_CORE_TD );
				$new['tourism_people'] = __( 'People', TOURISM_CORE_TD );
				$new['tourism_email']  = __( 'Email', TOURISM_CORE_TD );
				$new['tourism_status'] = __( 'Status', TOURISM_CORE_TD );
			}
		}
		return $new;
	}

	public function render( $column, $post_id ) {
		switch ( (string) $column ) {
			case 'tourism_tour':
				$tour_id = (int) get_post_meta( $post_id, 'tourism_tour_id', true );
				if ( $tour_id ) {
					$title = get_the_title( $tour_id );
					$link  = get_edit_post_link( $tour_id );
					if ( $link ) {
						echo '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>';
					} else {
						echo esc_html( $title );
					}
				}
				break;
			case 'tourism_date':
				echo esc_html( (string) get_post_meta( $post_id, 'tourism_date', true ) );
				break;
			case 'tourism_people':
				echo esc_html( (string) get_post_meta( $post_id, 'tourism_people', true ) );
				break;
			case 'tourism_email':
				$email = (string) get_post_meta( $post_id, 'tourism_customer_email', true );
				if ( $email ) {
					echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
				}
				break;
			case 'tourism_status':
				$status = get_post_status( $post_id );
				echo esc_html( $this->label_for_status( $status ) );
				break;
		}
	}

	public function sortable( $columns ) {
		$columns['tourism_date']   = 'tourism_date';
		$columns['tourism_people'] = 'tourism_people';
		return $columns;
	}

	public function sort_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-booking' !== $screen->id ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( 'tourism_date' === $orderby ) {
			$query->set( 'meta_key', 'tourism_date' );
			$query->set( 'orderby', 'meta_value' );
		}
		if ( 'tourism_people' === $orderby ) {
			$query->set( 'meta_key', 'tourism_people' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	private function label_for_status( $status ) {
		switch ( (string) $status ) {
			case 'booking_pending':
				return __( 'Pending', TOURISM_CORE_TD );
			case 'booking_confirmed':
				return __( 'Confirmed', TOURISM_CORE_TD );
			case 'booking_cancelled':
				return __( 'Cancelled', TOURISM_CORE_TD );
			default:
				return $status;
		}
	}
}

