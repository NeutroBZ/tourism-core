<?php
/** Reusable public tour sections for shortcodes and theme templates. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tourism_core_tour_card_fields( $fields ) {
	$allowed = array( 'image', 'title', 'duration', 'price', 'difficulty', 'group_size', 'max_elevation', 'excerpt', 'category' );
	$fields = is_array( $fields ) ? $fields : explode( ',', is_scalar( $fields ) ? (string) $fields : '' );
	return array_values( array_unique( array_intersect( $allowed, array_map( 'sanitize_key', array_filter( $fields, 'is_scalar' ) ) ) ) );
}

/** Plain data for custom cards; escape values when rendering your own HTML. */
function tourism_core_get_tour_card_data( $post_id ) {
	$post_id = absint( $post_id );
	if ( 'tour' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) || post_password_required( $post_id ) ) { return array(); }
	$data = array( 'id' => $post_id, 'title' => get_the_title( $post_id ), 'url' => get_permalink( $post_id ), 'currency' => tourism_core_get_tour_currency( $post_id ) );
	foreach ( array( 'duration', 'difficulty', 'group_size', 'max_elevation' ) as $field ) {
		$value = get_post_meta( $post_id, 'tourism_' . $field, true );
		$data[ $field ] = is_scalar( $value ) ? wp_strip_all_tags( (string) $value ) : '';
		if (function_exists('travel_t')) $data[$field] = travel_t($data[$field]);
	}
	$price = get_post_meta( $post_id, 'tourism_price_from', true );
	$data['price'] = is_scalar( $price ) && is_numeric( $price ) && is_finite( (float) $price ) && (float) $price >= 0 && $data['currency'] ? $data['currency'] . ' ' . number_format_i18n( (float) $price, 2 ) : '';
	$intro = get_post_meta( $post_id, 'tourism_intro_description', true );
	$data['excerpt'] = wp_trim_words( wp_strip_all_tags( is_string( $intro ) ? $intro : '' ), 24 );
	$terms = get_the_terms( $post_id, 'tour_category' );
	$data['category'] = is_array( $terms ) ? implode( ', ', wp_list_pluck( $terms, 'name' ) ) : '';
	$data['image_id'] = absint( get_post_meta( $post_id, '_tour_hero_image', true ) );
	if ( ! $data['image_id'] || ! wp_attachment_is_image( $data['image_id'] ) ) { $data['image_id'] = get_post_thumbnail_id( $post_id ); }
	$data['image_url'] = '';
	if ( ! $data['image_id'] ) {
		$gallery = get_post_meta( $post_id, 'tourism_gallery_images', true );
		foreach ( is_array( $gallery ) ? $gallery : array() as $image ) {
			$url = is_array( $image ) && isset( $image['url'] ) && is_string( $image['url'] ) ? esc_url_raw( $image['url'], array( 'http', 'https' ) ) : '';
			if ( $url && preg_match( '#^https?://#i', $url ) ) { $data['image_url'] = $url; break; }
		}
	}
	return $data;
}

/** Public tour data for completely custom theme markup; preserves the outer loop. */
function tourism_core_get_tours( $args = array() ) {
	if ( ! tourism_core_is_module_enabled( 'tours' ) ) { return array(); }
	$args = is_array( $args ) ? $args : array();
	if ( ! isset( $args['limit'] ) && isset( $args['posts_per_page'] ) ) { $args['limit'] = $args['posts_per_page']; }
	$args = wp_parse_args( $args, array( 'category' => '', 'tour_type' => '', 'limit' => 6, 'columns' => 3, 'order' => 'DESC', 'orderby' => 'date', 'fields' => array( 'image', 'title', 'duration', 'price' ) ) );
	$order = is_string( $args['order'] ) ? strtoupper( $args['order'] ) : 'DESC';
	$orderby = is_string( $args['orderby'] ) && in_array( $args['orderby'], array( 'date', 'title', 'menu_order', 'modified', 'rand' ), true ) ? $args['orderby'] : 'date';
	$query_args = array( 'post_type' => 'tour', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => max( 1, min( 48, (int) $args['limit'] ) ), 'order' => 'ASC' === $order ? 'ASC' : 'DESC', 'orderby' => $orderby, 'no_found_rows' => true, 'ignore_sticky_posts' => true );
	foreach ( array( 'category' => 'tour_category', 'tour_type' => 'tour_type' ) as $key => $taxonomy ) {
		if ( ! empty( $args[ $key ] ) && is_string( $args[ $key ] ) ) {
			$query_args['tax_query'][] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => array_map( static function ($slug) use ($taxonomy) { return tourism_core_language_term_slug(sanitize_title($slug), $taxonomy); }, explode( ',', $args[ $key ] ) ) );
		}
	}
	$query = new WP_Query( $query_args );
	$tours = array();
	foreach ( $query->posts as $tour ) {
		$card = tourism_core_get_tour_card_data( $tour->ID );
		if ( $card ) { $tours[] = $card; }
	}
	return $tours;
}

/** Return HTML without altering the surrounding WordPress loop. */
function tourism_core_render_tours( $args = array() ) {
	$args = wp_parse_args( is_array( $args ) ? $args : array(), array( 'columns' => 3, 'fields' => array( 'image', 'title', 'duration', 'price' ) ) );
	$fields = tourism_core_tour_card_fields( $args['fields'] );
	if ( ! $fields ) { return ''; }
	$html = '';
	foreach ( tourism_core_get_tours( $args ) as $card ) {
		$html .= Tourism_Core_Template::render( 'tour-card.php', array( 'post_id' => $card['id'], 'card' => $card, 'fields' => $fields ) );
	}
	if ( '' === $html ) { return ''; }
	$columns = max( 1, min( 4, (int) $args['columns'] ) );
	return '<div class="tourism-core-list tourism-core-list--columns-' . $columns . '">' . $html . '</div>';
}
