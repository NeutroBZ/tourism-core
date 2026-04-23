<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function tourism_core_get_tour_destinations( $tour_id ) {
	$tour_id = (int) $tour_id;
	if ( $tour_id <= 0 ) {
		return array();
	}

	$ids = get_post_meta( $tour_id, 'tourism_destinations', true );
	if ( ! is_array( $ids ) ) {
		return array();
	}

	$out = array();
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( $id > 0 && 'destination' === get_post_type( $id ) ) {
			$out[] = $id;
		}
	}

	return array_values( array_unique( $out ) );
}

function tourism_core_tour_has_destination( $tour_id, $destination_id ) {
	$destinations = tourism_core_get_tour_destinations( $tour_id );
	return in_array( (int) $destination_id, $destinations, true );
}

/**
 * Query tours filtered by tour_type taxonomy term and/or destination ID relation.
 *
 * @param array{tour_type?:string,destination_id?:int,posts_per_page?:int} $args
 * @return WP_Query
 */
function tourism_core_query_tours( $args = array() ) {
	$args = is_array( $args ) ? $args : array();

	$ppp           = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 10;
	$tour_type     = isset( $args['tour_type'] ) ? (string) $args['tour_type'] : '';
	$destination_id = isset( $args['destination_id'] ) ? (int) $args['destination_id'] : 0;

	$q_args = array(
		'post_type'      => 'tour',
		'posts_per_page' => $ppp,
	);

	if ( $tour_type ) {
		$q_args['tax_query'] = array(
			array(
				'taxonomy' => 'tour_type',
				'field'    => 'slug',
				'terms'    => array( sanitize_title( $tour_type ) ),
			),
		);
	}

	if ( $destination_id > 0 ) {
		$q_args['meta_query'] = array(
			array(
				'key'     => 'tourism_destinations',
				'value'   => '"' . $destination_id . '"',
				'compare' => 'LIKE',
			),
		);
	}

	return new WP_Query( $q_args );
}


