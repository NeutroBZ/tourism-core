<?php
/** php -c <site-php.ini> tools/export-headless.php <private-output-directory> */
if ( 'cli' !== PHP_SAPI ) { exit; }
define( 'DISABLE_WP_CRON', true ); define( 'WP_HTTP_BLOCK_EXTERNAL', true );
$directory = isset( $argv[1] ) ? realpath( $argv[1] ) : false;
$wp_root = dirname( __DIR__, 4 );
if ( ! $directory || ! is_dir( $directory ) || str_starts_with( strtolower( str_replace( '\\', '/', $directory ) ) . '/', strtolower( str_replace( '\\', '/', $wp_root ) ) . '/' ) ) {
	throw new InvalidArgumentException( 'Selecciona un directorio privado existente fuera del sitio público.' );
}
$_SERVER['HTTP_HOST'] = 'palota-2026.local';
require $wp_root . '/wp-load.php';
$origin = get_option( 'tourism_headless_frontend_url', '' );
if ( ! $origin ) { throw new RuntimeException( 'Configura tourism_headless_frontend_url antes de exportar.' ); }
$snapshot = ( new Tourism_Core_Headless_Export() )->snapshot( $origin );
$json = wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
if ( false === $json || false === file_put_contents( $directory . '/snapshot.json', $json, LOCK_EX ) ) { throw new RuntimeException( 'No se pudo guardar el contrato.' ); }
echo wp_json_encode( array( 'contract_version' => 1, 'posts' => count( $snapshot['posts'] ), 'routes' => count( $snapshot['routes'] ), 'sha256' => hash( 'sha256', $json ), 'peak_memory_bytes' => memory_get_peak_usage( true ) ) ) . "\n";
