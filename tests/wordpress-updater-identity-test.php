<?php
/** Local regression for the real WordPress plugin identifier. No external HTTP. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$_SERVER['HTTP_HOST'] = 'palota-2026.local'; $_SERVER['REQUEST_METHOD'] = 'GET';
require dirname( __DIR__, 4 ) . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Local only' ); }
$plugin = 'tourism-core/tourism-core.php';
if ( $plugin !== plugin_basename( TOURISM_CORE_FILE ) ) { throw new RuntimeException( 'Plugin basename must match the identifier used by WordPress' ); }
$key = 'tourism_core_release_' . substr( hash( 'sha256', defined( 'TOURISM_CORE_GITHUB_TOKEN' ) ? trim( TOURISM_CORE_GITHUB_TOKEN ) : '' ), 0, 16 );
$original = get_site_transient( $key ); $original_error = get_site_transient( 'tourism_core_update_error' );
$http = static function ( $pre, $args, $url ) {
	if ( Tourism_Core_GitHub_Updater::API . '/releases/latest' !== $url ) { return $pre; }
	return array( 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'tag_name' => 'v999.0.0', 'draft' => false, 'prerelease' => false, 'assets' => array( array( 'name' => 'tourism-core.zip', 'state' => 'uploaded', 'id' => 42 ) ) ) ) );
};
add_filter( 'pre_http_request', $http, PHP_INT_MAX, 3 );
try {
	delete_site_transient( $key );
	$result = apply_filters( 'update_plugins_github.com', false, array( 'Version' => TOURISM_CORE_VERSION ), $plugin, array() );
	if ( '999.0.0' !== ( $result['version'] ?? '' ) ) { throw new RuntimeException( 'Registered native update hook did not recognize the WordPress plugin identifier' ); }
	echo "PASS: Canonical WordPress plugin identity and registered GitHub update hook\n";
} finally {
	remove_filter( 'pre_http_request', $http, PHP_INT_MAX );
	if ( false === $original ) { delete_site_transient( $key ); } else { set_site_transient( $key, $original, HOUR_IN_SECONDS ); }
	if ( false === $original_error ) { delete_site_transient( 'tourism_core_update_error' ); } else { set_site_transient( 'tourism_core_update_error', $original_error, HOUR_IN_SECONDS ); }
}
