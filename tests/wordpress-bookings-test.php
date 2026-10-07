<?php
/** Integration checks against the local WordPress database. No real emails are sent. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
$_SERVER['HTTP_HOST'] = 'travel-theme.local';
$_SERVER['REQUEST_METHOD'] = 'GET';
$wp_root = dirname( __DIR__, 4 );
require_once $wp_root . '/wp-includes/plugin.php';
$mail_calls = 0;
$mail_result = false;
add_filter( 'pre_wp_mail', static function ( $result, $attrs ) use ( &$mail_calls, &$mail_result ) {
	++$mail_calls;
	if ( ! $mail_result ) { do_action( 'wp_mail_failed', new WP_Error( 'test_mail_failure', 'Simulated mail transport failure' ) ); }
	return $mail_result;
}, PHP_INT_MAX, 2 );
require_once $wp_root . '/wp-load.php';
require_once $wp_root . '/wp-admin/includes/template.php';
require_once $wp_root . '/wp-admin/includes/class-wp-screen.php';
require_once $wp_root . '/wp-admin/includes/screen.php';
if ( ! class_exists( 'Tourism_Core_Booking_Validator' ) ) { throw new RuntimeException( 'Tourism Core must be active for this test.' ); }
function tc_assert( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	echo "PASS: $message\n";
}
function tc_error( $value, $code ) { return is_wp_error( $value ) && $code === $value->get_error_code(); }
$posts = array();
$users = array();
$transient_keys = array();
$original_user = get_current_user_id();
$original_modules = get_option( Tourism_Core_Config::OPTION_MODULES );
$token = strtolower( wp_generate_password( 10, false ) );
$ip = '198.51.100.123';
try {
	$config = Tourism_Core_Config::instance();
	$modules = $config->get_modules();
	$modules['bookings'] = true;
	$modules['tours'] = true;
	$config->set_modules( $modules );
	Tourism_Core_Booking_Permissions::upgrade();
	( new Tourism_Core_Booking_CPT() )->register_cpt();
	( new Tourism_Core_Booking_CPT() )->register_meta();
	( new Tourism_Core_Booking_Statuses() )->register_statuses();
	if ( ! has_filter( 'map_meta_cap', array( 'Tourism_Core_Booking_Permissions', 'restrict_meta_caps' ) ) ) {
		add_filter( 'map_meta_cap', array( 'Tourism_Core_Booking_Permissions', 'restrict_meta_caps' ), 10, 4 );
	}
	foreach ( array( 'publish', 'draft', 'private' ) as $status ) {
		$id = wp_insert_post( array( 'post_type' => 'tour', 'post_title' => 'Tourism Core test ' . $token, 'post_status' => $status ), true );
		tc_assert( ! is_wp_error( $id ) && $id > 0, 'Create isolated ' . $status . ' tour' );
		$tours[ $status ] = $id;
		$posts[] = $id;
	}
	$raw = array( 'tour_id' => $tours['publish'], 'name' => 'Integration Test', 'email' => $token . '@example.invalid', 'people' => '2', 'date' => current_time( 'Y-m-d' ), 'message' => "First line\nSecond line" );
	$data = Tourism_Core_Booking_Validator::validate( $raw );
	tc_assert( ! is_wp_error( $data ), 'Today and published tour accepted' );
	foreach ( array( 'draft', 'private' ) as $status ) {
		tc_assert( tc_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'tour_id' => $tours[ $status ] ) ) ), 'tour' ), 'Reject ' . $status . ' tour' );
	}
	foreach ( array( '2026-02-30', '2026-13-01', '2000-01-01', '2030-1-1', 'tomorrow' ) as $date ) {
		tc_assert( tc_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'date' => $date ) ) ), 'date' ), 'Reject invalid or past date ' . $date );
	}
	foreach ( array( '0', '-1', '101', '1.5', '2abc' ) as $people ) {
		tc_assert( tc_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'people' => $people ) ) ), 'people' ), 'Reject invalid group size ' . $people );
	}
	tc_assert( tc_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'name' => array( 'bad' ) ) ) ), 'fields' ), 'Reject array inputs without PHP warnings' );
	tc_assert( tc_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'message' => str_repeat( 'x', 5001 ) ) ) ), 'length' ), 'Reject oversized message' );
	tc_assert( tc_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'booking_website' => 'bot.example' ) ) ), 'spam' ), 'Honeypot blocks automated submission' );
	tc_assert( ! is_wp_error( Tourism_Core_Booking_Validator::validate( array_merge( $raw, array( 'date' => '' ) ) ) ), 'Optional date remains optional' );

	$lease_key = 'integration_' . $token;
	$first = new Tourism_Core_Lock( $lease_key );
	$second = new Tourism_Core_Lock( $lease_key );
	tc_assert( $first->acquire() && ! $second->acquire(), 'Only one database lease owner' );
	$lease_name = '_tourism_core_lock_' . hash( 'sha256', $lease_key );
	$wpdb->update( $wpdb->options, array( 'option_value' => '1:expired' ), array( 'option_name' => $lease_name ) );
	tc_assert( $second->acquire(), 'Expired lease can be recovered' );
	$first->release();
	$third = new Tourism_Core_Lock( $lease_key );
	tc_assert( ! $third->acquire(), 'Old owner cannot release a newer lease' );
	$second->release();
	tc_assert( $third->acquire(), 'Released lease can be acquired again' );
	$third->release();

	$guard = new Tourism_Core_Booking_Guard();
	tc_assert( 0 === $guard->begin( $data, $ip ), 'Begin protected request' );
	$other_guard = new Tourism_Core_Booking_Guard();
	tc_assert( tc_error( $other_guard->begin( $data, $ip ), 'busy' ), 'Concurrent duplicate is rejected before insertion' );
	$guard->release();
	$handler = new Tourism_Core_Booking_Handler( null );
	$transient_keys[] = 'tc_booking_ip_' . wp_hash( $ip );
	$transient_keys[] = 'tc_booking_email_' . wp_hash( strtolower( $data['email'] ) );
	for ( $i = 0; $i < 4; ++$i ) {
		$request = array_merge( $raw, array( 'message' => "First line\nSecond line $i" ) );
		$normalized = Tourism_Core_Booking_Validator::validate( $request );
		$transient_keys[] = 'tc_booking_duplicate_' . wp_hash( wp_json_encode( $normalized ) );
		$id = $handler->create_booking( $request, $ip );
		if ( $i < 3 ) {
			tc_assert( is_int( $id ) && $id > 0, 'Create valid request ' . ( $i + 1 ) );
			$posts[] = $id;
			$booking_id = $id;
			tc_assert( $id === $handler->create_booking( $request, $ip ), 'Duplicate reuses booking without another insert' );
			tc_assert( $request['message'] === get_post_meta( $id, 'tourism_message', true ), 'Message line breaks preserved' );
		} else {
			tc_assert( tc_error( $id, 'rate' ), 'Fourth distinct request from same email is rate limited' );
		}
	}

	for ( $i = 0; $i < 3; ++$i ) {
		$request = array_merge( $raw, array( 'email' => 'ip_' . $i . '_' . $token . '@example.invalid' ) );
		$normalized = Tourism_Core_Booking_Validator::validate( $request );
		$transient_keys[] = 'tc_booking_email_' . wp_hash( strtolower( $normalized['email'] ) );
		$transient_keys[] = 'tc_booking_duplicate_' . wp_hash( wp_json_encode( $normalized ) );
		$id = $handler->create_booking( $request, $ip );
		if ( $i < 2 ) {
			tc_assert( is_int( $id ) && $id > 0, 'Accept distinct email below shared IP limit' );
			$posts[] = $id;
		} else {
			tc_assert( tc_error( $id, 'rate' ), 'Sixth distinct request from same IP is rate limited' );
		}
	}

	$editor_id = wp_insert_user( array( 'user_login' => 'tc_test_' . $token, 'user_pass' => wp_generate_password(), 'user_email' => 'editor_' . $token . '@example.invalid', 'role' => 'editor' ) );
	tc_assert( ! is_wp_error( $editor_id ), 'Create isolated editor for access checks' );
	$users[] = $editor_id;
	wp_set_current_user( $editor_id );
	tc_assert( current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_tourism_bookings' ), 'Editing content does not grant booking access' );
	tc_assert( ! current_user_can( 'read_post', $booking_id ) && ! current_user_can( 'edit_post', $booking_id ) && ! current_user_can( 'delete_post', $booking_id ), 'Editor cannot read, edit or delete customer bookings' );
	$admin_ids = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	tc_assert( ! empty( $admin_ids ), 'Administrator exists for management checks' );
	wp_set_current_user( $admin_ids[0] );
	tc_assert( current_user_can( 'manage_tourism_bookings' ) && current_user_can( 'edit_post', $booking_id ), 'Administrator retains booking management' );
	$admin = new Tourism_Core_Admin();
	$_POST = array( 'tourism_core_booking_admin_nonce' => wp_create_nonce( 'tourism_core_save_booking_admin' ), 'tourism_booking_status' => 'booking_confirmed' );
	$admin->save_booking_admin( $booking_id, get_post( $booking_id ) );
	tc_assert( 'booking_confirmed' === get_post_status( $booking_id ), 'Real WordPress status update completes without recursion' );
	wp_set_current_user( $editor_id );
	$_POST = array( 'tourism_core_booking_admin_nonce' => wp_create_nonce( 'tourism_core_save_booking_admin' ), 'tourism_booking_status' => 'booking_cancelled' );
	$admin->save_booking_admin( $booking_id, get_post( $booking_id ) );
	tc_assert( 'booking_confirmed' === get_post_status( $booking_id ), 'Valid nonce cannot bypass booking permissions' );
	wp_set_current_user( $admin_ids[0] );
	$_POST = array();

	$emails = new Tourism_Core_Email_Manager();
	$before_mail = $mail_calls;
	tc_assert( ! $emails->send_booking_customer_confirmation( $booking_id ), 'Simulated mail failure reported' );
	$state = get_post_meta( $booking_id, '_tourism_email_customer', true );
	tc_assert( 'failed' === $state['status'] && 1 === $state['attempts'] && false !== strpos( $state['error'], 'Simulated' ), 'Mail failure and attempts recorded' );
	tc_assert( (bool) wp_next_scheduled( Tourism_Core_Email_Manager::RETRY_HOOK, array( $booking_id, 'customer' ) ), 'Failed mail schedules retry' );
	for ( $i = 2; $i <= 4; ++$i ) {
		wp_clear_scheduled_hook( Tourism_Core_Email_Manager::RETRY_HOOK, array( $booking_id, 'customer' ) );
		$emails->retry( $booking_id, 'customer' );
		$state = get_post_meta( $booking_id, '_tourism_email_customer', true );
		tc_assert( $i === $state['attempts'], 'Automatic retry attempt ' . $i . ' tracked' );
	}
	tc_assert( ! wp_next_scheduled( Tourism_Core_Email_Manager::RETRY_HOOK, array( $booking_id, 'customer' ) ), 'Retries stop after four total attempts' );
	$emails->retry( $booking_id, 'customer' );
	tc_assert( $mail_calls === $before_mail + 4, 'Exhausted notification is not sent repeatedly' );
	$mail_result = true;
	tc_assert( $emails->send_notification( $booking_id, 'customer', true ), 'Manual retry can restart failed notification' );
	$state = get_post_meta( $booking_id, '_tourism_email_customer', true );
	tc_assert( 'sent' === $state['status'] && '' === $state['error'], 'Accepted notification clears failure state' );
	$before_mail = $mail_calls;
	$emails->retry( $booking_id, 'customer' );
	tc_assert( $before_mail === $mail_calls, 'Successful notification is not resent by stale cron' );
	tc_assert( $emails->send_booking_admin_notice( $booking_id ), 'Agency notification tracked independently' );

	ob_start();
	$admin->render_booking_metabox( get_post( $booking_id ) );
	$markup = ob_get_clean();
	tc_assert( false !== strpos( $markup, 'Accepted by the mail service' ), 'Admin shows acceptance status instead of claiming delivery' );
	ob_start();
	echo Tourism_Core_Template::render( 'booking-form.php', array( 'tour_id' => $tours['publish'] ) );
	$form = ob_get_clean();
	tc_assert( false !== strpos( $form, 'booking_website' ) && false !== strpos( $form, 'max="100"' ), 'Real form includes spam trap and group limit' );

	$metaboxes = new Tourism_Core_Tour_Metaboxes();
	$metaboxes->add_meta_boxes();
	$packing_boxes = array();
	foreach ( $wp_meta_boxes['tour'] as $contexts ) {
		foreach ( $contexts as $boxes ) {
			foreach ( $boxes as $key => $box ) {
				if ( in_array( $key, array( 'tourism-core-tour-what-to-bring', 'tourism-core-tour-bring' ), true ) && $box ) { $packing_boxes[] = $key; }
			}
		}
	}
	tc_assert( array( 'tourism-core-tour-what-to-bring' ) === $packing_boxes, 'Only one packing metabox is registered' );
	echo "All WordPress integration checks passed; no actual email was sent.\n";
} finally {
	$_POST = array();
	wp_set_current_user( $original_user );
	foreach ( $posts as $id ) {
		foreach ( array( 'admin', 'customer' ) as $kind ) { wp_clear_scheduled_hook( Tourism_Core_Email_Manager::RETRY_HOOK, array( $id, $kind ) ); }
		wp_delete_post( $id, true );
	}
	if ( ! empty( $users ) ) {
		require_once $wp_root . '/wp-admin/includes/user.php';
		foreach ( $users as $id ) { wp_delete_user( $id ); }
	}
	foreach ( array_unique( $transient_keys ) as $key ) { delete_transient( $key ); }
	if ( is_array( $original_modules ) ) { Tourism_Core_Config::instance()->set_modules( $original_modules ); }
	else { delete_option( Tourism_Core_Config::OPTION_MODULES ); }
}
