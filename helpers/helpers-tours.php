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

/**
 * WeTravel account UID (same for all tours). Override via filter.
 *
 * @return string
 */
function tourism_core_get_wetravel_uid() {
	$uid = apply_filters( 'tourism_core_wetravel_uid', '1685394' );
	$uid = absint( $uid );
	return $uid > 0 ? (string) $uid : '';
}

/**
 * Saved WeTravel tour UUID from post meta.
 *
 * @param int|null $post_id Post ID, defaults to current post.
 * @return string
 */
function tourism_core_get_wetravel_button_code( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( $post_id <= 0 ) {
		return '';
	}

	$uuid = absint( get_post_meta( $post_id, '_wetravel_button_code', true ) );
	return $uuid > 0 ? (string) $uuid : '';
}

/**
 * WeTravel checkout embed URL for a UUID.
 *
 * @param string $uuid Tour UUID.
 * @return string
 */
function tourism_core_wetravel_checkout_url( $uuid ) {
	$uuid = (string) absint( $uuid );
	if ( $uuid === '' || $uuid === '0' ) {
		return '';
	}

	return 'https://www.wetravel.com/checkout_embed?uuid=' . rawurlencode( $uuid );
}

/**
 * Enqueue WeTravel embed script (opens checkout in popup).
 */
function tourism_core_enqueue_wetravel_checkout() {
	if ( wp_script_is( 'wetravel-embed-checkout', 'enqueued' ) || wp_script_is( 'wetravel-embed-checkout', 'done' ) ) {
		return;
	}

	wp_enqueue_script(
		'wetravel-embed-checkout',
		'https://cdn.wetravel.com/widgets/embed_checkout.js',
		array(),
		null,
		true
	);
}

/**
 * Render a WeTravel checkout button for the current tour.
 *
 * @param array{post_id?:int,label?:string,class?:string,echo?:bool} $args Arguments.
 * @return string HTML when echo is false.
 */
function tourism_core_render_wetravel_checkout_button( $args = array() ) {
	$args = wp_parse_args(
		is_array( $args ) ? $args : array(),
		array(
			'post_id' => get_the_ID(),
			'label'   => __( 'Book Now', TOURISM_CORE_TD ),
			'class'   => '',
			'echo'    => true,
		)
	);

	$uuid = tourism_core_get_wetravel_button_code( (int) $args['post_id'] );
	$uid  = tourism_core_get_wetravel_uid();
	if ( $uuid === '' || $uid === '' ) {
		return '';
	}

	tourism_core_enqueue_wetravel_checkout();

	$checkout_url = tourism_core_wetravel_checkout_url( $uuid );
	$classes      = trim( 'wtrvl-checkout_button ' . (string) $args['class'] );

	$html = sprintf(
		'<button type="button" class="%1$s" data-env="https://www.wetravel.com" data-version="v0.4" data-uid="%2$s" data-uuid="%3$s" href="%4$s">%5$s</button>',
		esc_attr( $classes ),
		esc_attr( $uid ),
		esc_attr( $uuid ),
		esc_url( $checkout_url ),
		esc_html( (string) $args['label'] )
	);

	if ( ! empty( $args['echo'] ) ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in sprintf.
		return '';
	}

	return $html;
}


