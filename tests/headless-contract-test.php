<?php
/** Read-only integration tests for the data consumed by Astro. No fixture posts or mail. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true ); define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$_SERVER['HTTP_HOST'] = 'palota-2026.local'; $_SERVER['REQUEST_METHOD'] = 'GET';
require dirname( __DIR__, 4 ) . '/wp-load.php';
$checks = 0;
function headless_check( $ok, $message ) {
	global $checks; $checks++;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
$server = rest_get_server(); wp_set_current_user( 0 );
$response = $server->dispatch( new WP_REST_Request( 'GET', '/tourism/v1/headless' ) );
headless_check( in_array( $response->get_status(), array( 401, 403 ), true ), 'Anonymous export access must be denied' );
$admins = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
wp_set_current_user( $admins[0] );
$permission = $server->get_routes()['/tourism/v1/headless'][0]['permission_callback'];
$export_cap = static function ( $caps ) { unset( $caps['manage_options'] ); $caps['tourism_export_headless'] = true; return $caps; };
add_filter( 'user_has_cap', $export_cap );
headless_check( true === $permission(), 'Dedicated build capability works without administrator permissions' );
remove_filter( 'user_has_cap', $export_cap );
$reader_cap = static function ( $caps ) { unset( $caps['manage_options'], $caps['tourism_export_headless'] ); return $caps; };
add_filter( 'user_has_cap', $reader_cap );
headless_check( false === $permission(), 'Ordinary reader cannot export' );
remove_filter( 'user_has_cap', $reader_cap );
$response = $server->dispatch( new WP_REST_Request( 'GET', '/tourism/v1/headless' ) );
headless_check( 200 === $response->get_status(), 'Authenticated export must succeed' );
headless_check( 'private, no-store' === ( $response->get_headers()['Cache-Control'] ?? '' ), 'Authenticated data must not be cached publicly' );
$snapshot = $response->get_data(); $by_id = array(); $seen = array(); $counts = array(); $ratings = 0;
headless_check( 1 === $snapshot['contract_version'], 'Contract version' );
headless_check( 'not_authorized_by_this_export' === $snapshot['production_indexing'], 'Export must not authorize indexing' );
foreach ( $snapshot['posts'] as $post ) { $by_id[ $post['id'] ] = $post; }
foreach ( $snapshot['posts'] as $post ) {
	$id = $post['id']; $original = get_post( $id );
	headless_check( 'publish' === $original->post_status && '' === $original->post_password, 'Only public content: ' . $id );
	headless_check( ! isset( $seen[ $post['path'] ] ), 'Unique path: ' . $id ); $seen[ $post['path'] ] = true;
	headless_check( $original->post_title === $post['title'], 'Title preserved: ' . $id );
	headless_check( hash( 'sha256', $original->post_content ) === $post['content']['source_sha256'], 'Original content fingerprint: ' . $id );
	headless_check( hash( 'sha256', $post['content']['html'] ) === $post['content']['public_sha256'], 'Export content fingerprint: ' . $id );
	$assembled = '';
	foreach ( $post['content']['segments'] as $segment ) { $assembled .= 'html' === $segment['type'] ? $segment['html'] : $segment['source']; }
	headless_check( $assembled === $post['content']['html'], 'Component extraction must preserve every byte: ' . $id );
	headless_check( ! empty( $post['seo']['title'] ) && ! preg_match( '/%%[a-z_]+%%/i', $post['seo']['title'] ), 'Resolved SEO title: ' . $id );
	headless_check( (bool) filter_var( $post['seo']['canonical'], FILTER_VALIDATE_URL ), 'Valid canonical: ' . $id );
	foreach ( $post['seo']['breadcrumbs'] as $crumb ) { headless_check( '' !== trim( $crumb['name'] ) && (bool) filter_var( $crumb['url'], FILTER_VALIDATE_URL ), 'Breadcrumb label and target available' ); }
	foreach ( $post['seo']['source_yoast'] as $key => $value ) {
		headless_check( is_scalar( $value ) || is_array( $value ), 'SEO allowlisted value: ' . $id );
		$stored = get_post_meta( $id, '_yoast_wpseo_' . $key, true );
		if ( ! preg_match( '~https?://~', wp_json_encode( $stored ) ) ) { headless_check( $stored === $value, 'Stored SEO field preserved: ' . $id . '/' . $key ); }
	}
	foreach ( $post['translations'] as $language => $translation ) {
		$other = $by_id[ $translation['id'] ] ?? null;
		headless_check( $other && $other['url'] === $translation['url'] && $other['language'] === $language, 'Translation target: ' . $id );
		headless_check( ( $other['translations'][ $post['language'] ]['id'] ?? null ) === $id, 'Reciprocal hreflang: ' . $id );
	}
	foreach ( $post['seo']['primary_terms'] as $taxonomy => $term ) {
		headless_check( has_term( $term['id'], $taxonomy, $id ), 'Resolved primary taxonomy is assigned to this content' );
		headless_check( $term['id'] === (int) get_post_meta( $id, '_yoast_wpseo_primary_' . $taxonomy, true ), 'Primary taxonomy uses current IDs' );
	}
	$public = $post; unset( $public['backend_url'] ); $json = wp_json_encode( $public );
	foreach ( $post['seo']['yoast_schema']['@graph'] ?? array() as $entity ) {
		if ( in_array( 'Person', (array) ( $entity['@type'] ?? array() ), true ) ) {
			headless_check( $entity['@id'] === $post['author']['url'] . '#person' && $entity['name'] === $post['author']['name'], 'Public author identity preserved: ' . $id );
		}
	}
	headless_check( ! preg_match( '~https?://[^/"\s]*\.local(?:/|["\\\s])~', $json ), 'No local URL in public payload: ' . $id );
	headless_check( ! str_contains( $json, '[REDACTED_EMAIL]' ), 'No broken email placeholders: ' . $id );
	headless_check( ! isset( $post['post_meta'], $post['user_email'], $post['password'] ), 'No unrestricted records: ' . $id );
	$counts[ $post['type'] ] = ( $counts[ $post['type'] ] ?? 0 ) + 1;
	if ( 'post' === $post['type'] && 'None' !== get_post_meta( $id, '_yoast_wpseo_schema_article_type', true ) ) {
		headless_check( $post['article_schema']['@id'] === $post['url'] . '#article' && $post['article_schema']['author']['name'] === $post['author']['name'], 'Article identity and original author available independently of backend noindex' );
		headless_check( $post['article_schema']['datePublished'] === str_replace( ' ', 'T', $original->post_date_gmt ) . '+00:00', 'Original article publication date preserved' );
	}
	if ( 'tour' === $post['type'] ) {
		$entity = $post['tour_schema']['@graph'][0];
		headless_check( $entity['@id'] === $post['url'] . '#tour', 'Schema identity follows preserved tour route' );
		headless_check( isset( $entity['description'], $entity['image'] ), 'Tour schema has description and images' );
		if ( isset( $entity['offers'] ) ) { headless_check( (float) $entity['offers']['price'] === (float) $post['fields']['tourism_price_from'] && $entity['offers']['priceCurrency'] === $post['fields']['currency'], 'Offer and public data agree' ); }
		if ( isset( $entity['aggregateRating'] ) ) { headless_check( $entity['aggregateRating']['reviewCount'] === count( $post['fields']['reviews'] ), 'Schema counts the public reviews' ); }
		$ratings += count( $post['fields']['reviews'] );
	}
}
$paths = array_column( $snapshot['routes'], 'path' );
headless_check( count( $paths ) === count( array_unique( $paths ) ), 'Unique route inventory' );
foreach ( $snapshot['redirects'] as $redirect ) {
	headless_check( $redirect['from'] !== $redirect['to'], 'No self redirect' );
	headless_check( ! in_array( $redirect['from'], $paths, true ), 'No generated page shadows a redirect' );
}
$html = '<p>Before</p>[not_registered id="a"]<b>Preserve me</b>[/not_registered]<p>After</p>';
$segments = Tourism_Core_Headless_Export::components( $html );
headless_check( 3 === count( $segments ) && 'not_registered' === $segments[1]['name'] && '<b>Preserve me</b>' === $segments[1]['content'], 'Unknown component is retained without execution' );
$no_legacy = static function () { return array(); };
add_filter( 'pre_option_tourism_headless_public_asset_manifest', $no_legacy );
try {
	$method = new ReflectionMethod( Tourism_Core_Headless_Export::class, 'assets' ); $method->setAccessible( true );
	$one = $snapshot['media'][0]; $one['sizes'] = array();
	$assets = $method->invoke( new Tourism_Core_Headless_Export(), array( $one ) );
	headless_check( 1 === count( $assets ) && $assets[0]['sha256'] === hash_file( 'sha256', get_attached_file( $one['id'] ) ), 'New media is discovered and hashed without a manual migration' );
	$one['url'] = trailingslashit( wp_get_upload_dir()['baseurl'] ) . '../../wp-config.php'; $rejected = false;
	try { $method->invoke( new Tourism_Core_Headless_Export(), array( $one ) ); } catch ( RuntimeException $error ) { $rejected = true; }
	headless_check( $rejected, 'Asset path traversal cannot expose files outside uploads' );
} finally { remove_filter( 'pre_option_tourism_headless_public_asset_manifest', $no_legacy ); }
foreach ( array( 'http://example.com', 'https://example.com/path', 'https://user@example.com', 'https://example.com:8443', 'https://example.com/#fragment' ) as $bad ) {
	$rejected = false; try { ( new Tourism_Core_Headless_Export() )->snapshot( $bad ); } catch ( InvalidArgumentException $error ) { $rejected = true; }
	headless_check( $rejected, 'Invalid frontend origin rejected' );
}
if ( 'https://palotoaamazontravel.com' === $snapshot['frontend_origin'] ) {
	headless_check( 388 === count( $snapshot['posts'] ) && 72 === $counts['tour'] && 257 === $counts['post'] && 59 === $counts['page'], 'All migrated public content present' );
	headless_check( 90 === $ratings && 9 === count( $snapshot['menus'] ), 'Reviews and menus preserved' );
	foreach ( $snapshot['posts'] as $post ) { if ( 'tour' === $post['type'] ) { headless_check( (bool) preg_match( '~^/(?:fr/)?tour/[^/]+/$~', $post['path'] ), 'Original Tour Master URL retained' ); } }
}
$output = $argv[1] ?? sys_get_temp_dir() . '/tourism-headless-contract.json';
file_put_contents( $output, wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
$report = array( 'result' => 'PASS', 'checks' => $checks, 'counts' => $counts, 'menus' => count( $snapshot['menus'] ), 'terms' => count( $snapshot['terms'] ), 'routes' => count( $paths ), 'redirects' => count( $snapshot['redirects'] ), 'assets' => count( $snapshot['assets'] ), 'snapshot' => $output, 'peak_memory_bytes' => memory_get_peak_usage( true ) );
file_put_contents( dirname( $output ) . '/contract-validation.json', wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
echo wp_json_encode( $report ) . "\n";
