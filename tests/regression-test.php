<?php
/** Run directly with PHP; isolated regression checks with WordPress test doubles. */
define( 'ABSPATH', __DIR__ );
define( 'TOURISM_CORE_PATH', dirname( __DIR__ ) . '/' );
define( 'TOURISM_CORE_TD', 'tourism-core' );
$GLOBALS['tc_meta'] = array();
$GLOBALS['tc_options'] = array();
$GLOBALS['tc_status'] = 'booking_pending';
$GLOBALS['tc_updates'] = 0;
function __( $text, $domain = '' ) { return $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function settings_errors( $option ) {}
function settings_fields( $group ) {}
function submit_button() {}
function selected( $actual, $expected ) { if ( $actual === $expected ) { echo 'selected'; } }
function tourism_core_get_agency( $key ) { return $GLOBALS['tc_whatsapp'] ?? ''; }
function wp_script_is( $handle, $status ) { return false; }
function wp_enqueue_script( $handle, $url, $deps, $version, $footer ) {}
function apply_filters( $hook, $value ) { return $value; }
function add_filter( $hook, $callback ) { $GLOBALS['tc_filters'][ $hook ] = $callback; }
function get_option( $key, $default = false ) { return $GLOBALS['tc_options'][ $key ] ?? $default; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function shortcode_atts( $defaults, $args ) { return array_merge( $defaults, array_intersect_key( $args, $defaults ) ); }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ) ); }
function sanitize_text_field( $value ) { return trim( $value ); }
function is_email( $value ) { return filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function sanitize_email( $value ) { return $value; }
function get_the_title( $id ) { return 'Tour & Adventure'; }
function wp_unslash( $value ) { return $value; }
function wp_verify_nonce( $nonce, $action ) { return 'valid' === $nonce; }
function current_user_can( $cap, $id = 0 ) { return true; }
function wp_is_post_revision( $id ) { return false; }
function get_post_type( $id ) { return 12 === $id ? 'tour' : 'booking'; }
function get_the_ID() { return 12; }
function tourism_core_is_module_enabled( $module ) { return true; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['tc_meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['tc_meta'][ $id ][ $key ] = $value; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function esc_url_raw( $url, $protocols = null ) { return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : ''; }
function esc_url( $url ) { return htmlspecialchars( $url, ENT_QUOTES ); }
function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function add_settings_error( $option, $key, $message ) { $GLOBALS['tc_error'] = $key; }
function sanitize_title( $value ) { return $value; }
class WP_Query { public $args; public function __construct( $args ) { $this->args = $args; } }
function wp_update_post( $args ) {
	if ( ++$GLOBALS['tc_updates'] > 3 ) { throw new RuntimeException( 'Recursive booking update' ); }
	$GLOBALS['tc_status'] = $args['post_status'];
	$GLOBALS['tc_admin']->save_booking_admin( $args['ID'], (object) array( 'post_type' => 'booking', 'post_status' => $args['post_status'] ) );
}
require TOURISM_CORE_PATH . 'modules/payments/class-payment-providers.php';
require TOURISM_CORE_PATH . 'helpers/helpers-tours.php';
require TOURISM_CORE_PATH . 'admin/class-admin.php';
function check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	echo "PASS: $message\n";
}
$payments = new Tourism_Core_Payment_Providers();
check( '1685394' === tourism_core_get_wetravel_uid(), 'Legacy WeTravel account preserved' );
$GLOBALS['tc_options'][ Tourism_Core_Payment_Providers::OPTION ] = array( 'wetravel_uid' => '987654' );
check( '987654' === tourism_core_get_wetravel_uid(), 'Configured WeTravel account used' );
check( '987654' === $payments->sanitize_settings( array( 'wetravel_uid' => 'bad' ) )['wetravel_uid'], 'Invalid account preserves previous value' );
$GLOBALS['tc_whatsapp'] = '+51 999 888 777';
update_post_meta( 12, 'tourism_price_from', '150.50' );
$html = Tourism_Core_Payment_Providers::render_button();
check( false !== strpos( $html, 'wa.me/51999888777' ) && false === strpos( $html, 'paypal.com' ) && false === strpos( $html, 'wtrvl-checkout_button' ), 'No configured methods shows only WhatsApp' );
check( false !== strpos( $html, rawurlencode( 'Tour & Adventure' ) ), 'WhatsApp message identifies the tour' );
update_post_meta( 12, '_wetravel_button_code', 51840477 );
$html = Tourism_Core_Payment_Providers::render_button();
check( false !== strpos( $html, 'data-uuid="51840477"' ) && false === strpos( $html, 'wa.me/' ) && false === strpos( $html, 'paypal.com' ), 'WeTravel alone shows only WeTravel' );
$GLOBALS['tc_options'][ Tourism_Core_Payment_Providers::OPTION ] = $payments->sanitize_settings( array( 'paypal_email' => 'global@example.com', 'paypal_currency' => 'EUR' ) );
update_post_meta( 12, 'tourism_paypal_email', 'old-tour@example.com' );
update_post_meta( 12, 'tourism_payment_provider', 'none' );
parse_str( parse_url( Tourism_Core_Payment_Providers::paypal_url( 12 ), PHP_URL_QUERY ), $paypal_args );
check( 'global@example.com' === $paypal_args['business'] && '150.50' === $paypal_args['amount'] && 'EUR' === $paypal_args['currency_code'], 'PayPal uses one global email, price and currency' );
check( 'Tour & Adventure' === $paypal_args['item_name'] && '12' === $paypal_args['item_number'], 'PayPal checkout includes tour identity' );
$html = Tourism_Core_Payment_Providers::render_button();
check( false !== strpos( $html, 'paypal.com/cgi-bin/webscr' ) && false !== strpos( $html, 'wtrvl-checkout_button' ) && false === strpos( $html, 'wa.me/' ), 'Both configured methods show both buttons despite old provider setting' );
check( 2 === substr_count( Tourism_Core_Payment_Providers::render_button( array( 'label' => '<Book>' ) ), '&lt;Book&gt;' ), 'Both payment button labels escaped' );
check( 'global@example.com' === $payments->sanitize_settings( array( 'paypal_email' => 'invalid' ) )['paypal_email'], 'Invalid general email preserves previous value' );
$GLOBALS['tc_options'][ Tourism_Core_Payment_Providers::OPTION ] = $payments->sanitize_settings( array( 'wetravel_uid' => '' ) );
$html = Tourism_Core_Payment_Providers::render_button();
check( false !== strpos( $html, 'paypal.com/cgi-bin/webscr' ) && false === strpos( $html, 'wtrvl-checkout_button' ) && false === strpos( $html, 'wa.me/' ), 'Blank WeTravel account shows only PayPal' );
update_post_meta( 12, 'tourism_price_from', '0' );
check( false !== strpos( Tourism_Core_Payment_Providers::render_button(), 'wa.me/' ), 'Invalid PayPal price falls back to WhatsApp' );
update_post_meta( 12, 'tourism_price_from', '150.50' );
$GLOBALS['tc_options'][ Tourism_Core_Payment_Providers::OPTION ] = $payments->sanitize_settings( array( 'paypal_email' => '' ) );
update_post_meta( 12, 'tourism_paypal_checkout_url', 'https://www.paypal.com/ncp/payment/old' );
check( false !== strpos( Tourism_Core_Payment_Providers::render_button(), 'wa.me/' ) && '' === Tourism_Core_Payment_Providers::paypal_url( 12 ), 'Blank general email hides PayPal despite old tour email or link' );
$GLOBALS['tc_whatsapp'] = '';
check( '' === Tourism_Core_Payment_Providers::render_button(), 'Missing WhatsApp does not create a broken button' );
$GLOBALS['tc_whatsapp'] = '+51 999 888 777';
$GLOBALS['tc_options'][ Tourism_Core_Payment_Providers::OPTION ] = $payments->sanitize_settings( array( 'wetravel_uid' => '987654' ) );
update_post_meta( 12, '_wetravel_button_code', '' );
check( false !== strpos( Tourism_Core_Payment_Providers::render_button(), 'wa.me/' ), 'Missing tour UUID falls back despite configured WeTravel account' );
ob_start();
$payments->page();
$page = ob_get_clean();
check( false !== strpos( $page, 'name="tourism_core_payments[paypal_email]"' ) && false !== strpos( $page, 'name="tourism_core_payments[wetravel_uid]"' ) && false === strpos( $page, 'default_provider' ) && false === strpos( $page, ' hidden' ), 'Payments page shows both optional methods without a selector' );
$query = tourism_core_query_tours( array( 'destination_id' => 12 ) );
$pattern = '/' . $query->args['meta_query'][0]['value'] . '/';
check( 1 === preg_match( $pattern, serialize( array( 12, 123 ) ) ), 'Integer destination matched' );
check( 0 === preg_match( $pattern, serialize( array( 123 ) ) ), 'Partial destination excluded' );
check( 0 === preg_match( $pattern, serialize( array( 12 => 999 ) ) ), 'Array index does not match destination' );
check( 0 === preg_match( $pattern, serialize( array( 0 => 999, 12 => 111 ) ) ), 'Later array index does not match destination' );
check( 1 === preg_match( $pattern, serialize( array( '999', 12 ) ) ), 'Integer destination after legacy value matched' );
check( false !== strpos( serialize( array( '12' ) ), $query->args['meta_query'][1]['value'] ), 'Legacy string destination matched' );
$GLOBALS['tc_admin'] = new Tourism_Core_Admin();
$_POST = array( 'tourism_core_booking_admin_nonce' => 'valid', 'tourism_booking_status' => 'booking_confirmed' );
$GLOBALS['tc_admin']->save_booking_admin( 99, (object) array( 'post_type' => 'booking', 'post_status' => 'booking_pending' ) );
check( 1 === $GLOBALS['tc_updates'] && 'booking_confirmed' === $GLOBALS['tc_status'], 'Booking status changes once without recursion' );
echo "All regression checks passed.\n";
