<?php
/** Local-only API integration checks. Mail is intercepted; synthetic private posts are removed. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'TOURISM_TEST_HEADLESS_MODE', 'enabled' );
require __DIR__ . '/headless-test-bootstrap.php';
define( 'DISABLE_WP_CRON', true ); define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$_SERVER['HTTP_HOST'] = 'palota-2026.local'; $_SERVER['REQUEST_METHOD'] = 'POST';
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Run this test only in Local.' ); }
$server = rest_get_server(); $checks = 0; $created = array(); $mail = 0;
$fingerprint = hash( 'sha256', random_bytes( 32 ) );
$transient = 'tourism_headless_contact_' . hash_hmac( 'sha256', $fingerprint, wp_salt() );
$payload = array( 'name' => 'Synthetic headless check', 'email' => 'headless-test@example.invalid', 'subject' => 'Synthetic headless check', 'message' => 'This fixture is removed after the local API test.', 'fingerprint' => $fingerprint );
function contact_api_check( $ok, $message ) { global $checks; $checks++; if ( ! $ok ) { throw new RuntimeException( $message ); } }
$send = static function ( $body ) use ( $server ) { $request = new WP_REST_Request( 'POST', '/tourism/v1/headless/contact' ); $request->set_body_params( $body ); return $server->dispatch( $request ); };
$capture = static function ( $id, $post, $update ) use ( &$created ) { if ( ! $update && 'tourism_message' === $post->post_type ) { $created[] = $id; } };
$mail_stub = static function () use ( &$mail ) { $mail++; return true; };
$limited = static function ( $caps ) { unset( $caps['manage_options'], $caps['tourism_submit_contact'] ); $caps['tourism_export_headless'] = true; return $caps; };
$runtime = static function () { return array( 'read' => true, 'tourism_submit_contact' => true ); };
add_action( 'wp_insert_post', $capture, 10, 3 ); add_filter( 'pre_wp_mail', $mail_stub, PHP_INT_MAX );
try {
	wp_set_current_user( 0 ); contact_api_check( in_array( $send( $payload )->get_status(), array( 401, 403 ), true ), 'Anonymous submission denied' );
	$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) ); wp_set_current_user( $admins[0] );
	add_filter( 'user_has_cap', $limited );
	contact_api_check( 403 === $send( $payload )->get_status(), 'Read-only build credentials cannot submit contact messages' );
	remove_filter( 'user_has_cap', $limited );
	foreach ( array( array( 'email' => 'not-an-email' ), array( 'name' => array( 'invalid' ) ), array( 'subject' => str_repeat( 'x', 201 ) ), array( 'message' => '' ), array( 'fingerprint' => 'untrusted-raw-ip' ) ) as $bad ) {
		contact_api_check( 400 === $send( array_merge( $payload, $bad ) )->get_status(), 'Invalid contact input rejected' );
	}
	contact_api_check( ! $created && 0 === $mail, 'Invalid input creates no message and sends no mail' );
	add_filter( 'user_has_cap', $runtime );
	$response = $send( $payload );
	contact_api_check( 202 === $response->get_status() && array( 'accepted' => true ) === $response->get_data(), 'Valid submission accepted without exposing private record IDs' );
	contact_api_check( 1 === count( $created ) && 1 === $mail, 'One private message and one intercepted notification' );
	$id = $created[0];
	contact_api_check( 'private' === get_post_status( $id ) && 'tourism_message' === get_post_type( $id ), 'Contact is stored privately' );
	contact_api_check( $payload['email'] === get_post_meta( $id, '_contact_email', true ) && $payload['message'] === get_post_field( 'post_content', $id ), 'Submitted data reaches private storage' );
	contact_api_check( 'accepted' === get_post_meta( $id, '_notification_status', true ), 'Notification status records the intercepted mail result' );
	contact_api_check( 429 === $send( $payload )->get_status() && 1 === count( $created ), 'Repeated visitor fingerprint is rate limited' );
	contact_api_check( false === get_post_type_object( 'tourism_message' )->public && false === get_post_type_object( 'tourism_message' )->show_in_rest, 'Messages have no public REST collection' );
} finally {
	remove_filter( 'user_has_cap', $limited ); remove_filter( 'user_has_cap', $runtime ); remove_filter( 'pre_wp_mail', $mail_stub, PHP_INT_MAX ); remove_action( 'wp_insert_post', $capture, 10 );
	foreach ( $created as $id ) { wp_delete_post( $id, true ); }
	delete_transient( $transient ); wp_set_current_user( 0 );
}
$report = array( 'result' => 'PASS', 'checks' => $checks, 'synthetic_messages_removed' => count( $created ), 'notifications_intercepted' => $mail, 'external_mail_sent' => false );
if ( isset( $argv[1] ) ) { file_put_contents( $argv[1], wp_json_encode( $report, JSON_PRETTY_PRINT ) ); }
echo wp_json_encode( $report ) . "\n";
