<?php
/** Temporary local admin and tour for browser checks; never run through HTTP. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = 'travel-theme.local';
$_SERVER['REQUEST_METHOD'] = 'GET';
$root = dirname( __DIR__, 4 );
require_once $root . '/wp-includes/plugin.php';
add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
require_once $root . '/wp-load.php';
require_once $root . '/wp-admin/includes/user.php';
$state_path = getenv( 'TOURISM_EDITOR_STATE' );
if ( ! $state_path || ! wp_get_environment_type() || 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Run only on a local site with TOURISM_EDITOR_STATE set.' ); }
$action = $argv[1] ?? '';
if ( 'prepare' === $action ) {
	if ( file_exists( $state_path ) ) { throw new RuntimeException( 'Clean up the previous fixture first.' ); }
	$user = wp_insert_user( array( 'user_login' => 'tc_editor_check_' . wp_generate_password( 10, false ), 'user_pass' => wp_generate_password( 40 ), 'role' => 'administrator' ) );
	if ( is_wp_error( $user ) ) { throw new RuntimeException( $user->get_error_message() ); }
	wp_set_current_user( $user );
	$id = wp_insert_post( array( 'post_type' => 'tour', 'post_status' => 'draft', 'post_title' => 'Tourism Core browser fixture' ) );
	$attachment = wp_insert_attachment( array( 'post_title' => 'Tourism Core media fixture', 'post_mime_type' => 'image/png', 'post_status' => 'inherit', 'guid' => home_url( '/wp-content/plugins/tourism-core/assets/ico/tours.png' ) ), false, $id );
	wp_update_attachment_metadata( $attachment, array( 'width' => 32, 'height' => 32, 'sizes' => array() ) );
	$expiry = time() + 3600;
	$base_url = untrailingslashit( home_url() );
	$cookie_host = wp_parse_url( $base_url, PHP_URL_HOST );
	$token = WP_Session_Tokens::get_instance( $user )->create( $expiry );
	$state = array( 'baseUrl' => $base_url, 'userId' => $user, 'tourId' => $id, 'attachmentId' => $attachment, 'cookies' => array(
		array( 'name' => AUTH_COOKIE, 'value' => wp_generate_auth_cookie( $user, $expiry, 'auth', $token ), 'domain' => $cookie_host, 'path' => '/', 'httpOnly' => true, 'sameSite' => 'Lax' ),
		array( 'name' => LOGGED_IN_COOKIE, 'value' => wp_generate_auth_cookie( $user, $expiry, 'logged_in', $token ), 'domain' => $cookie_host, 'path' => '/', 'httpOnly' => true, 'sameSite' => 'Lax' ),
	) );
	file_put_contents( $state_path, wp_json_encode( $state ) );
	update_post_meta( $id, 'tourism_intro_title', 'A local adventure' );
	update_post_meta( $id, 'tourism_intro_description', '<p>Original introduction.</p>' );
	update_post_meta( $id, 'tourism_price_from', '125.50' );
	update_post_meta( $id, 'tourism_price_currency', 'USD' );
	update_post_meta( $id, 'tourism_trip_origin', 'Cusco' );
	update_post_meta( $id, 'tourism_tour_sku', 'UI-TEST' );
	update_post_meta( $id, 'tourism_itinerary_items', array(
		array( 'day' => 'Primer día', 'description' => '<p>Contenido uno.</p>', 'features' => array( array( 'label' => 'Lugar', 'value' => 'Cusco' ) ) ),
		array( 'day' => 'Segundo día', 'description' => '<p>Contenido dos.</p>', 'features' => array() ),
	) );
	update_post_meta( $id, 'tourism_faqs', array( array( 'question' => 'Primera pregunta', 'answer' => '<p>Primera respuesta.</p>' ), array( 'question' => 'Segunda pregunta', 'answer' => '<p>Segunda respuesta.</p>' ) ) );
	update_post_meta( $id, 'tourism_highlights', array( array( 'title' => 'Primer destacado', 'content' => '<p>Detalles uno.</p>' ), array( 'title' => 'Segundo destacado', 'content' => '<p>Detalles dos.</p>' ) ) );
	update_post_meta( $id, 'tourism_what_to_expect', array( array( 'title' => 'Primera experiencia', 'content' => '<p>Expectativa uno.</p>' ), array( 'title' => 'Segunda experiencia', 'content' => '<p>Expectativa dos.</p>' ) ) );
	update_post_meta( $id, 'tourism_includes_new', array( array( 'title' => 'Primer incluido', 'content' => '<p>Incluido uno.</p>' ), array( 'title' => 'Segundo incluido', 'content' => '<p>Incluido dos.</p>' ) ) );
	update_post_meta( $id, 'tourism_excludes_new', array( array( 'title' => 'Primer excluido', 'content' => '<p>Excluido uno.</p>' ), array( 'title' => 'Segundo excluido', 'content' => '<p>Excluido dos.</p>' ) ) );
	echo "Fixture prepared; credentials saved locally without printing them.\n";
} elseif ( 'verify' === $action ) {
	$state = json_decode( file_get_contents( $state_path ), true );
	$id = $state['tourId'];
	$itinerary = get_post_meta( $id, 'tourism_itinerary_items', true );
	$checks = array(
		'Price in hidden section saved' => '189.75' === (string) get_post_meta( $id, 'tourism_price_from', true ),
		'Currency saved through original schema handler' => 'EUR' === get_post_meta( $id, 'tourism_price_currency', true ),
		'Relocated origin saved' => 'Lima' === get_post_meta( $id, 'tourism_trip_origin', true ),
		'Advanced SKU preserved' => 'UI-TEST' === get_post_meta( $id, 'tourism_tour_sku', true ),
		'Duplicated and reordered days saved' => 3 === count( $itinerary ) && 'Segundo día' === $itinerary[0]['day'] && 'Primer día' === $itinerary[1]['day'] && 'Copia del primer día' === $itinerary[2]['day'],
		'Rich text and nested features preserved' => false !== strpos( $itinerary[2]['description'], 'Contenido uno.' ) && 'Cusco' === $itinerary[2]['features'][0]['value'],
		'Unvisited introduction preserved' => false !== strpos( get_post_meta( $id, 'tourism_intro_description', true ), 'Original introduction.' ),
		'Collapsed FAQ saved' => 'Respuesta actualizada.' === trim( wp_strip_all_tags( get_post_meta( $id, 'tourism_faqs', true )[1]['answer'] ) ),
		'Collapsed highlights preserved' => 2 === count( get_post_meta( $id, 'tourism_highlights', true ) ),
	);
	foreach ( $checks as $label => $ok ) { if ( ! $ok ) { throw new RuntimeException( $label ); } echo "PASS: $label\n"; }
} elseif ( 'verify-experience' === $action ) {
	$state = json_decode( file_get_contents( $state_path ), true );
	foreach ( array( 'tourism_highlights', 'tourism_what_to_expect', 'tourism_includes_new', 'tourism_excludes_new' ) as $key ) {
		$rows = get_post_meta( $state['tourId'], $key, true );
		if ( 2 !== count( $rows ) || 'Nuevo ' . $key !== $rows[0]['title'] || 'Texto en código ' . $key !== trim( wp_strip_all_tags( $rows[0]['content'] ) ) || false === strpos( $rows[1]['content'], 'tourism-media-check' ) ) {
			throw new RuntimeException( 'Experience content, ordering or inserted image lost: ' . $key );
		}
		echo "PASS: Saved Experience text, order and media: $key\n";
	}
} elseif ( 'cleanup' === $action ) {
	if ( ! file_exists( $state_path ) ) { exit; }
	$state = json_decode( file_get_contents( $state_path ), true );
	if ( ! empty( $state['attachmentId'] ) ) { wp_delete_attachment( $state['attachmentId'], true ); }
	wp_delete_post( $state['tourId'], true );
	wp_delete_user( $state['userId'] );
	unlink( $state_path );
	echo "Fixture tour, temporary admin and sessions removed.\n";
} else { throw new RuntimeException( 'Use prepare, verify or cleanup.' ); }
