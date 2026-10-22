<?php
/** Run separately with enabled, disabled, and legacy. No settings or content are changed. */
if ( 'cli' !== PHP_SAPI ) { exit; }
$mode = $argv[1] ?? 'legacy';
if ( ! in_array( $mode, array( 'enabled', 'disabled', 'legacy' ), true ) ) { throw new RuntimeException( 'Unknown mode' ); }
define( 'TOURISM_TEST_HEADLESS_MODE', $mode );
require __DIR__ . '/headless-test-bootstrap.php';
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
// Isolate this plugin from site-specific MU plugins that also register contact messages.
define( 'WPMU_PLUGIN_DIR', __DIR__ . '/fixtures/no-mu-plugins' );
$_SERVER['HTTP_HOST'] = 'palota-2026.local';
$_SERVER['REQUEST_METHOD'] = 'GET';
require dirname( __DIR__, 4 ) . '/wp-load.php';
$enabled = 'enabled' === $mode;
$checks = 0;
function module_check( $ok, $message ) {
	global $checks;
	$checks++;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
module_check( false === Tourism_Core_Module_Registry::default_modules()['headless'], 'Headless defaults to off' );
module_check( $enabled === Tourism_Core_Config::instance()->is_module_enabled( 'headless' ), 'Effective module setting' );
foreach ( array( 'Tourism_Core_Headless_Export', 'Tourism_Core_Headless_Reviews', 'Tourism_Core_Headless_Contact', 'Tourism_Core_Contact_Mail' ) as $class ) {
	module_check( $enabled === class_exists( $class, false ), 'Conditional loading: ' . $class );
}
$server = rest_get_server();
$routes = $server->get_routes();
foreach ( array( '/tourism/v1/headless', '/tourism/v1/headless/contact' ) as $route ) {
	module_check( $enabled === isset( $routes[$route] ), 'Conditional route: ' . $route );
	if ( ! $enabled ) {
		$method = str_ends_with( $route, '/contact' ) ? 'POST' : 'GET';
		module_check( 404 === $server->dispatch( new WP_REST_Request( $method, $route ) )->get_status(), 'Disabled route returns 404' );
	}
}
module_check( $enabled === post_type_exists( 'tourism_message' ), 'Conditional message section' );
module_check( $enabled === ( false !== has_action( 'phpmailer_init', array( 'Tourism_Core_Contact_Mail', 'local_transport' ) ) ), 'Conditional mail hook' );
module_check( post_type_exists( 'tour' ) && shortcode_exists( 'tour_list' ) && shortcode_exists( 'tour_payment_button' ), 'WordPress tours and shortcodes remain available' );
$modules = Tourism_Core_Config::instance()->get_modules();
$modules['headless'] = true;
module_check( true === Tourism_Core_Module_Registry::sanitize_modules( $modules )['headless'], 'Checked switch is accepted' );
unset( $modules['headless'] );
module_check( false === Tourism_Core_Module_Registry::sanitize_modules( $modules )['headless'], 'Unchecked switch is disabled' );
ob_start();
( new Tourism_Core_Site_Metaboxes() )->render_modules( null );
$html = ob_get_clean();
module_check( false !== strpos( $html, 'name="tourism_core_modules[headless]"' ), 'Configuration renders the switch' );
echo wp_json_encode( array( 'result' => 'PASS', 'mode' => $mode, 'checks' => $checks ) ) . "\n";
