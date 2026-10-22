<?php
/** Offline updater regression checks. No WordPress database or external HTTP. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'ABSPATH', __DIR__ );
define( 'TOURISM_CORE_FILE', dirname( __DIR__ ) . '/tourism-core.php' );
define( 'TOURISM_CORE_VERSION', '0.6.1' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
if ( 'private' === ( $argv[1] ?? '' ) ) { define( 'TOURISM_CORE_GITHUB_TOKEN', 'synthetic-test-token' ); }
$cache = $requests = $hooks = array(); $response = array(); $downloaded = null; $checks = 0;
class WP_Error { public $code; public function __construct( $code, $message = '' ) { $this->code = $code; } }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function plugin_basename( $file ) { return 'tourism-core/' . basename( $file ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$hook] = $callback; }
function add_action( $hook, $callback ) { $GLOBALS['hooks'][$hook] = $callback; }
function get_site_transient( $key ) { return $GLOBALS['cache'][$key]['value'] ?? false; }
function set_site_transient( $key, $value, $ttl ) { $GLOBALS['cache'][$key] = array( 'value' => $value, 'ttl' => $ttl ); }
function delete_site_transient( $key ) { unset( $GLOBALS['cache'][$key] ); }
function wp_safe_remote_get( $url, $args ) {
	$GLOBALS['requests'][] = array( $url, $args );
	if ( isset( $args['filename'] ) ) { file_put_contents( $args['filename'], 'synthetic ZIP' ); }
	return $GLOBALS['response'];
}
function wp_remote_retrieve_response_code( $response ) { return $response['code'] ?? 0; }
function wp_remote_retrieve_body( $response ) { return $response['body'] ?? ''; }
function wp_remote_retrieve_header( $response, $name ) { return $response['headers'][$name] ?? ''; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function wp_tempnam( $name ) { return tempnam( sys_get_temp_dir(), 'tourism-updater-' ); }
function wp_delete_file( $file ) { unlink( $file ); }
function download_url( $url, $timeout ) { $GLOBALS['downloaded'] = $url; return '/synthetic/download.zip'; }
function esc_url( $url ) { return htmlspecialchars( $url, ENT_QUOTES ); }
function check_update( $ok, $message ) {
	$GLOBALS['checks']++;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
function release_response( $changes = array() ) {
	return array( 'code' => 200, 'body' => json_encode( array_merge( array(
		'tag_name' => 'v0.6.2', 'draft' => false, 'prerelease' => false,
		'assets' => array( array( 'name' => 'tourism-core.zip', 'state' => 'uploaded', 'id' => 42, 'url' => 'https://evil.invalid/ignored' ) ),
	), $changes ) ) );
}
require dirname( __DIR__ ) . '/includes/class-github-updater.php';
$updater = new Tourism_Core_GitHub_Updater(); $updater->register();
check_update( isset( $hooks['update_plugins_github.com'], $hooks['plugins_api'], $hooks['upgrader_pre_download'] ), 'Native WordPress hooks registered' );
$response = release_response();
check_update( 'existing' === $updater->update( 'existing', array(), 'other/other.php', array() ) && ! $requests, 'Other plugins untouched without HTTP' );
$update = $updater->update( false, array(), 'tourism-core/tourism-core.php', array() );
check_update( '0.6.2' === $update['version'] && Tourism_Core_GitHub_Updater::API . '/releases/assets/42' === $update['package'], 'Stable release uses canonical asset API URL' );
check_update( ! isset( $update['autoupdate'] ), 'WordPress controls automatic installation' );
check_update( 0 === $requests[0][1]['redirection'], 'Authenticated metadata requests cannot redirect credentials' );
check_update( defined( 'TOURISM_CORE_GITHUB_TOKEN' ) === isset( $requests[0][1]['headers']['Authorization'] ), 'Credentials supplied only when configured' );
$updater->release(); check_update( 1 === count( $requests ), 'Successful metadata cached' );
$info = $updater->information( false, 'plugin_information', (object) array( 'slug' => 'tourism-core' ) );
check_update( 'Humble Labz' === $info->author && $info->download_link === $update['package'], 'Plugin information matches release' );
check_update( 'existing' === $updater->information( 'existing', 'plugin_information', (object) array( 'slug' => 'other' ) ), 'Other plugin information untouched' );
foreach ( array( array( 'draft' => true ), array( 'prerelease' => true ), array( 'tag_name' => 'v0.7.0-beta' ), array( 'tag_name' => '../main' ), array( 'assets' => array() ), array( 'assets' => array( array( 'name' => 'tourism-core.zip', 'state' => 'uploaded', 'id' => '../escape' ) ) ) ) as $bad ) {
	$cache = array(); $response = release_response( $bad );
	check_update( array() === $updater->release(), 'Invalid or incomplete release rejected' );
}
foreach ( array( new WP_Error( 'offline' ), array( 'code' => 404 ), array( 'code' => 403 ), array( 'code' => 200, 'body' => 'invalid JSON' ) ) as $bad ) {
	$cache = array(); $response = $bad; $before = count( $requests );
	check_update( false === $updater->update( false, array(), 'tourism-core/tourism-core.php', array() ), 'HTTP/API failure does not invent an update' );
	$updater->release(); check_update( $before + 1 === count( $requests ), 'Failure is cached briefly' );
}
$package = Tourism_Core_GitHub_Updater::API . '/releases/assets/42';
$before = count( $requests );
foreach ( array( 'https://evil.invalid/archive.zip', $package . '?token=bad', 'https://api.github.com/repos/other/repo/releases/assets/42' ) as $other ) {
	check_update( false === $updater->download( false, $other, null ), 'Unrelated downloads untouched' );
}
check_update( count( $requests ) === $before, 'No credentials sent for unrelated packages' );
check_update( 'already-handled' === $updater->download( 'already-handled', $package, null ), 'Existing download result preserved' );
check_update( false === $updater->download( false, $package, null, array( 'plugin' => 'other/other.php' ) ), 'Another plugin upgrade is not intercepted' );
$response = array( 'code' => 200 ); $file = $updater->download( false, $package, null );
check_update( is_file( $file ) && 'synthetic ZIP' === file_get_contents( $file ), 'Direct binary asset downloaded to a temporary file' );
unlink( $file );
$response = array( 'code' => 302, 'headers' => array( 'location' => 'https://release-assets.githubusercontent.com/example.zip?signed=value' ) );
check_update( '/synthetic/download.zip' === $updater->download( false, $package, null ) && $downloaded === $response['headers']['location'], 'Signed asset redirect downloaded separately without authorization headers' );
foreach ( array( 'http://release-assets.githubusercontent.com/file', 'https://evil.invalid/file', 'https://release-assets.githubusercontent.com.evil.invalid/file', 'https://user:secret@release-assets.githubusercontent.com/file', 'https://release-assets.githubusercontent.com:444/file' ) as $bad ) {
	$response['headers']['location'] = $bad;
	check_update( is_wp_error( $updater->download( false, $package, null ) ), 'Unsafe asset redirect rejected' );
}
$response = array( 'code' => 401 );
check_update( is_wp_error( $updater->download( false, $package, null ) ), 'Authentication failure stops installation' );
echo 'PASS: ' . $checks . ' updater checks (' . ( defined( 'TOURISM_CORE_GITHUB_TOKEN' ) ? 'private' : 'public' ) . ")\n";
