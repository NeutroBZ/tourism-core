<?php
/** Read-only WordPress regression checks; metadata overrides never reach the database. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$_SERVER['HTTP_HOST'] = 'palota-2026.local';
require dirname( __DIR__, 4 ) . '/wp-load.php';
$ids = get_posts( array( 'post_type' => 'tour', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'lang' => '', 'suppress_filters' => true ) );
if ( ! $ids ) { throw new RuntimeException( 'A published tour is required.' ); }
$id = $ids[0]; $checks = 0;
$overrides = array( 'tourism_intro_description' => '', 'tourism_itinerary_items' => array( array( 'day' => 'Day 1', 'description' => '<p>Visible itinerary &amp; local guide.</p>' ) ), 'tourism_price_from' => '', 'tourism_price_valid_until' => '', 'tourism_review_items' => array(), 'tourism_schema_disabled' => false );
$filter = static function ( $value, $post_id, $key ) use ( $id, &$overrides ) {
	return (int) $post_id === (int) $id && array_key_exists( $key, $overrides ) ? array( $overrides[ $key ] ) : $value;
};
$visible = static function () { return true; };
function readiness_check( $ok, $message ) {
	global $checks;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	$checks++; echo "PASS: $message\n";
}
add_filter( 'get_post_metadata', $filter, PHP_INT_MAX, 3 );
add_filter( 'tourism_core_tour_extras_visible', $visible, PHP_INT_MAX );
try {
	$entity = Tourism_Core_Tour_Schema::build( $id )['@graph'][0];
	readiness_check( 'Visible itinerary & local guide.' === $entity['description'], 'Missing introduction uses existing visible itinerary text' );
	readiness_check( 'TouristTrip' === $entity['@type'] && ! isset( $entity['offers'], $entity['aggregateRating'] ), 'No price or reviews emits a trip without an invalid Product' );
	readiness_check( ! isset( $entity['brand'], $entity['sku'], $entity['category'], $entity['additionalProperty'] ), 'Trip-only entity omits Product-specific properties' );
	$overrides['tourism_price_from'] = '123.45';
	$entity = Tourism_Core_Tour_Schema::build( $id )['@graph'][0];
	readiness_check( in_array( 'Product', $entity['@type'], true ) && '123.45' === $entity['offers']['price'], 'Adding a price restores Product and Offer automatically' );
	readiness_check( ! isset( $entity['offers']['availability'], $entity['offers']['priceValidUntil'] ), 'No invented availability or expiry date' );
	$overrides['tourism_price_from'] = '';
	$overrides['tourism_review_items'] = array( array( 'approved' => true, 'author' => 'Read-only fixture', 'content' => 'Example review in memory only.', 'rating' => 4 ) );
	$entity = Tourism_Core_Tour_Schema::build( $id )['@graph'][0];
	readiness_check( in_array( 'Product', $entity['@type'], true ) && 1 === $entity['aggregateRating']['reviewCount'] && 4.0 === $entity['aggregateRating']['ratingValue'], 'Review-only Product retains its real aggregate rating without a fake offer' );
	$overrides['tourism_review_items'][0]['approved'] = false;
	$entity = Tourism_Core_Tour_Schema::build( $id )['@graph'][0];
	readiness_check( 'TouristTrip' === $entity['@type'] && empty( $entity['aggregateRating'] ), 'Unapproved reviews never create stars or enable Product' );
	$overrides['tourism_intro_description'] = '<p>Original introduction.</p>';
	readiness_check( 'Original introduction.' === Tourism_Core_Tour_Schema::description( $id ), 'Existing introduction takes precedence' );
	$overrides['tourism_intro_description'] = ''; $overrides['tourism_itinerary_items'] = array();
	readiness_check( '' === Tourism_Core_Tour_Schema::description( $id ), 'No invented description when visible content is absent' );
} finally {
	remove_filter( 'get_post_metadata', $filter, PHP_INT_MAX );
	remove_filter( 'tourism_core_tour_extras_visible', $visible, PHP_INT_MAX );
}
echo "$checks read-only schema checks passed.\n";
