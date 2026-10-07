<?php
/** Local WordPress integration checks for tour structured data. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = 'travel-theme.local';
$_SERVER['REQUEST_METHOD'] = 'GET';
$wp_root = dirname( __DIR__, 4 );
require_once $wp_root . '/wp-includes/plugin.php';
add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
require_once $wp_root . '/wp-load.php';
require_once $wp_root . '/wp-admin/includes/template.php';
if ( ! class_exists( 'Tourism_Core_Tour_Schema' ) ) { throw new RuntimeException( 'Activate Tourism Core before testing.' ); }
function schema_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	echo "PASS: $message\n";
}
$ids = array();
$original_user = get_current_user_id();
$original_modules = get_option( Tourism_Core_Config::OPTION_MODULES );
$original_payments = get_option( Tourism_Core_Payment_Providers::OPTION );
$agency_filter = static function () { return array( 'commercial_name' => 'Test Agency', 'company_name' => 'Test Agency Legal', 'email' => 'test@example.invalid', 'phone' => '+51 999888777', 'address' => 'Cusco', 'facebook' => 'https://example.invalid/agency' ); };
try {
	$config = Tourism_Core_Config::instance();
	$modules = $config->get_modules(); $modules['tours'] = true; $config->set_modules( $modules );
	add_filter( 'tourism_core_tour_schema_agency', $agency_filter );
	$admin_ids = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
	wp_set_current_user( $admin_ids[0] );
	$settings = new Tourism_Core_Tour_Schema_Metabox();
	$settings->register_meta();
	foreach ( array( 'publish', 'draft' ) as $status ) {
		$id = wp_insert_post( array( 'post_type' => 'tour', 'post_title' => 'Tourism schema integration test', 'post_status' => $status ), true );
		schema_check( is_int( $id ) && $id > 0, 'Create isolated ' . $status . ' tour' );
		$ids[] = $id; $tours[ $status ] = $id;
	}
	$id = $tours['publish'];
	$destination = wp_insert_post( array( 'post_type' => 'destination', 'post_title' => 'Machu Picchu', 'post_status' => 'publish' ), true );
	$ids[] = $destination;
	$private_destination = wp_insert_post( array( 'post_type' => 'destination', 'post_title' => 'Private test destination', 'post_status' => 'private' ), true );
	$ids[] = $private_destination;
	update_post_meta( $id, 'tourism_intro_description', '<p>Explore Cusco &amp; Machu Picchu.</p><p>With a local guide.</p>' );
	update_post_meta( $id, 'tourism_gallery_images', array( array( 'url' => 'https://example.invalid/tour.jpg' ), array( 'url' => 'https://example.invalid/tour.jpg' ), array( 'url' => 'javascript:alert(1)' ) ) );
	update_post_meta( $id, 'tourism_duration', '2 days / 1 night' );
	update_post_meta( $id, 'tourism_difficulty', 'Moderate' );
	update_post_meta( $id, 'tourism_destinations', array( $destination, $private_destination, $destination ) );
	update_post_meta( $id, 'tourism_itinerary_items', array( array( 'day' => 'Día 1: Cusco', 'description' => '<p>Walking tour.</p>', 'features' => array( array( 'label' => 'Meals', 'value' => 'Lunch' ) ) ), array( 'day' => 'Día 2: Machu Picchu', 'description' => 'Guided visit.' ) ) );
	update_post_meta( $id, 'tourism_includes_new', array( array( 'title' => 'Local guide', 'content' => '<p>Professional bilingual guide</p>' ) ) );
	update_post_meta( $id, 'tourism_price_from', '150.25' );
	$_POST = array( 'tourism_schema_metabox_present' => '1', 'tourism_core_schema_nonce' => wp_create_nonce( 'tourism_core_save_schema' ), 'tourism_price_currency' => 'USD', 'tourism_offer_availability' => 'InStock', 'tourism_price_valid_until' => '2099-12-31', 'tourism_tour_sku' => 'MP-2D', 'tourism_trip_origin' => 'Cusco' );
	$settings->save( $id, get_post( $id ) ); $_POST = array();
	$graph = Tourism_Core_Tour_Schema::build( $id );
	$tour = $graph['@graph'][0];
	schema_check( array( 'TouristTrip', 'Product' ) === $tour['@type'] && 2 === count( $graph['@graph'] ), 'One dual-typed tour entity and one agency' );
	schema_check( 'Explore Cusco & Machu Picchu. With a local guide.' === $tour['description'], 'Description uses visible introduction and cleans HTML' );
	schema_check( array( 'https://example.invalid/tour.jpg' ) === $tour['image'], 'Images deduplicated and unsafe URLs rejected' );
	schema_check( 'MP-2D' === $tour['sku'] && 'Cusco' === $tour['tripOrigin']['name'], 'New SKU and departure fields mapped' );
	schema_check( '150.25' === $tour['offers']['price'] && 'USD' === $tour['offers']['priceCurrency'], 'Offer uses current price and canonical currency' );
	schema_check( 'https://schema.org/InStock' === $tour['offers']['availability'] && '2099-12-31' === $tour['offers']['priceValidUntil'], 'Explicit availability and price validity mapped' );
	schema_check( $tour['@id'] === $tour['offers']['itemOffered']['@id'], 'Offer refers to the same tour identity' );
	schema_check( $graph['@graph'][1]['@id'] === $tour['provider']['@id'] && $tour['provider'] === $tour['offers']['seller'], 'Provider and seller refer to the same agency' );
	schema_check( 2 === count( $tour['subTrip'] ) && $tour['@id'] === $tour['subTrip'][0]['partOfTrip']['@id'], 'Itinerary days are linked trip stages' );
	schema_check( 'Walking tour. Meals: Lunch' === $tour['subTrip'][0]['description'], 'Itinerary features preserved in stage description' );
	schema_check( 1 === $tour['itinerary']['numberOfItems'] && 'Place' === $tour['itinerary']['itemListElement'][0]['@type'], 'Only public destinations included once as places' );
	schema_check( 'https://schema.org/ItemListUnordered' === $tour['itinerary']['itemListOrder'], 'No chronological destination order invented' );
	schema_check( ! isset( $tour['aggregateRating'], $tour['review'] ) && ! isset( $tour['duration'] ), 'No fabricated reviews or unsupported Trip duration property' );
	schema_check( count( $tour['additionalProperty'] ) >= 3, 'Tour details and included items represented as Product properties' );
	schema_check( array() === Tourism_Core_Tour_Schema::diagnostics( $id ), 'Complete data passes diagnostics' );

	$_POST = array( 'tourism_schema_metabox_present' => '1', 'tourism_core_schema_nonce' => wp_create_nonce( 'tourism_core_save_schema' ), 'tourism_price_currency' => 'XXX', 'tourism_price_valid_until' => '2099-02-30', 'tourism_trip_origin' => array( 'bad' ) );
	$settings->save( $id, get_post( $id ) ); $_POST = array();
	schema_check( 'USD' === get_post_meta( $id, 'tourism_price_currency', true ) && '2099-12-31' === get_post_meta( $id, 'tourism_price_valid_until', true ) && 'Cusco' === get_post_meta( $id, 'tourism_trip_origin', true ), 'Invalid replacements preserve existing fields' );
	wp_set_current_user( 0 );
	$_POST = array( 'tourism_schema_metabox_present' => '1', 'tourism_core_schema_nonce' => wp_create_nonce( 'tourism_core_save_schema' ), 'tourism_tour_sku' => 'UNAUTHORIZED' );
	$settings->save( $id, get_post( $id ) ); $_POST = array();
	schema_check( 'MP-2D' === get_post_meta( $id, 'tourism_tour_sku', true ), 'Valid nonce does not bypass tour edit permissions' );
	wp_set_current_user( $admin_ids[0] );
	$_POST = array( 'tourism_core_schema_nonce' => 'invalid', 'tourism_schema_metabox_present' => '1', 'tourism_tour_sku' => 'BADNONCE' );
	$settings->save( $id, get_post( $id ) ); $_POST = array();
	schema_check( 'MP-2D' === get_post_meta( $id, 'tourism_tour_sku', true ), 'Invalid nonce cannot overwrite fields' );
	$_POST = array( 'tourism_core_schema_nonce' => wp_create_nonce( 'tourism_core_save_schema' ) );
	$settings->save( $id, get_post( $id ) ); $_POST = array();
	schema_check( 'USD' === get_post_meta( $id, 'tourism_price_currency', true ), 'Partial editor submission preserves schema fields' );
	update_post_meta( $id, 'tourism_schema_disabled', true );
	schema_check( array() === Tourism_Core_Tour_Schema::build( $id ), 'Per-tour opt-out prevents duplicate markup' );
	update_post_meta( $id, 'tourism_schema_disabled', false );
	schema_check( array() === Tourism_Core_Tour_Schema::build( $tours['draft'] ), 'Draft tours do not emit structured data' );
	wp_update_post( array( 'ID' => $id, 'post_password' => 'protected' ) );
	schema_check( array() === Tourism_Core_Tour_Schema::build( $id ), 'Password-protected tour content is not leaked' );
	wp_update_post( array( 'ID' => $id, 'post_password' => '' ) );
	foreach ( array( '', '-10', 'not a price' ) as $price ) {
		update_post_meta( $id, 'tourism_price_from', $price );
		$incomplete = Tourism_Core_Tour_Schema::build( $id );
		schema_check( ! isset( $incomplete['@graph'][0]['offers'] ), 'Missing or invalid price does not create an invented Offer' );
	}
	update_post_meta( $id, 'tourism_price_from', '0' );
	schema_check( '0.00' === Tourism_Core_Tour_Schema::offer( $id )['price'], 'Explicit zero-price tour remains a valid free Offer' );
	update_post_meta( $id, 'tourism_price_from', '150.25' );
	update_post_meta( $id, 'tourism_price_valid_until', '2000-01-01' );
	schema_check( array() === Tourism_Core_Tour_Schema::offer( $id ) && ! empty( Tourism_Core_Tour_Schema::diagnostics( $id ) ), 'Expired price omitted and flagged in the panel' );
	update_post_meta( $id, 'tourism_price_valid_until', '' );
	update_post_meta( $id, 'tourism_offer_availability', '' );
	schema_check( ! isset( Tourism_Core_Tour_Schema::offer( $id )['availability'] ), 'Availability is not inferred from a payment button' );
	$payments = Tourism_Core_Payment_Providers::settings(); $payments['paypal_email'] = 'global@example.invalid'; update_option( Tourism_Core_Payment_Providers::OPTION, $payments );
	update_post_meta( $id, 'tourism_price_currency', 'EUR' );
	parse_str( wp_parse_url( Tourism_Core_Payment_Providers::paypal_url( $id ), PHP_URL_QUERY ), $paypal );
	schema_check( 'EUR' === Tourism_Core_Tour_Schema::offer( $id )['priceCurrency'] && 'EUR' === $paypal['currency_code'], 'Schema and PayPal share the tour currency' );
	update_post_meta( $id, 'tourism_price_currency', 'PEN' );
	schema_check( 'PEN' === Tourism_Core_Tour_Schema::offer( $id )['priceCurrency'] && '' === Tourism_Core_Payment_Providers::paypal_url( $id ), 'PEN is represented honestly without a hidden PayPal conversion' );
	update_post_meta( $id, 'tourism_price_currency', 'USD' );
	update_post_meta( $id, 'tourism_includes_new', array( array( 'title' => 'Safety', 'content' => '&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;' ) ) );
	$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'tour', 'p' => $id ) );
	$schema = new Tourism_Core_Tour_Schema();
	ob_start(); $schema->output(); $schema->output(); $html = ob_get_clean();
	schema_check( 1 === substr_count( $html, 'id="tourism-core-tour-schema"' ) && 1 === substr_count( $html, '</script>' ), 'JSON-LD emitted once and script injection cannot break the element' );
	preg_match( '#<script[^>]*>(.*?)</script>#s', $html, $match );
	schema_check( is_array( json_decode( $match[1], true ) ) && JSON_ERROR_NONE === json_last_error(), 'Emitted JSON-LD parses successfully' );
	$facts = Tourism_Core_Tour_Schema::public_details( $id );
	schema_check( false !== strpos( $facts, 'MP-2D' ) && false !== strpos( $facts, 'Cusco' ), 'New SKU and origin are available as public tour facts' );
	ob_start(); $settings->render( get_post( $id ) ); $metabox = ob_get_clean();
	schema_check( false !== strpos( $metabox, 'tourism_price_currency' ) && false !== strpos( $metabox, 'tourism_schema_disabled' ), 'New editor fields and duplicate-markup opt-out rendered' );
	echo "All schema integration checks passed.\n";
} finally {
	$_POST = array(); wp_set_current_user( $original_user );
	remove_filter( 'tourism_core_tour_schema_agency', $agency_filter );
	foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
	if ( is_array( $original_modules ) ) { Tourism_Core_Config::instance()->set_modules( $original_modules ); } else { delete_option( Tourism_Core_Config::OPTION_MODULES ); }
	if ( false === $original_payments ) { delete_option( Tourism_Core_Payment_Providers::OPTION ); } else { update_option( Tourism_Core_Payment_Providers::OPTION, $original_payments ); }
}
