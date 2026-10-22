<?php
/** GitHub release updates for Tourism Core. Copyright (C) 2026 Humble Labz. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Tourism_Core_GitHub_Updater {
	const REPOSITORY = 'NeutroBZ/tourism-core';
	const API = 'https://api.github.com/repos/NeutroBZ/tourism-core';
	const URL = 'https://github.com/NeutroBZ/tourism-core';

	public function register() {
		add_filter( 'update_plugins_github.com', array( $this, 'update' ), 10, 4 );
		add_filter( 'plugins_api', array( $this, 'information' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'download' ), 10, 4 );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	private function token() {
		return defined( 'TOURISM_CORE_GITHUB_TOKEN' ) ? trim( (string) TOURISM_CORE_GITHUB_TOKEN ) : '';
	}

	private function cache_key() {
		return 'tourism_core_release_' . substr( hash( 'sha256', $this->token() ), 0, 16 );
	}

	private function headers( $accept ) {
		$headers = array( 'Accept' => $accept, 'X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'Tourism-Core/' . TOURISM_CORE_VERSION );
		if ( '' !== $this->token() ) { $headers['Authorization'] = 'Bearer ' . $this->token(); }
		return $headers;
	}

	/** Only stable releases with the canonical installable asset are eligible. */
	public function release() {
		$key = $this->cache_key();
		$cached = get_site_transient( $key );
		if ( false !== $cached ) { return $cached; }
		$response = wp_safe_remote_get( self::API . '/releases/latest', array(
			'headers' => $this->headers( 'application/vnd.github+json' ),
			'timeout' => 15, 'redirection' => 0, 'limit_response_size' => 262144,
		) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $this->failed( $key );
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] )
			|| ! preg_match( '/^v?(\d+\.\d+\.\d+)$/D', $data['tag_name'] ?? '', $match ) ) {
			return $this->failed( $key );
		}
		foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
			if ( 'tourism-core.zip' !== ( $asset['name'] ?? '' ) || 'uploaded' !== ( $asset['state'] ?? '' ) || empty( $asset['id'] ) ) { continue; }
			$id = (string) $asset['id'];
			if ( ! ctype_digit( $id ) || (int) $id < 1 ) { continue; }
			$release = array( 'version' => $match[1], 'package' => self::API . '/releases/assets/' . $id );
			set_site_transient( $key, $release, HOUR_IN_SECONDS );
			delete_site_transient( 'tourism_core_update_error' );
			return $release;
		}
		return $this->failed( $key );
	}

	private function failed( $key ) {
		set_site_transient( $key, array(), 5 * MINUTE_IN_SECONDS );
		set_site_transient( 'tourism_core_update_error', true, HOUR_IN_SECONDS );
		return array();
	}

	public function update( $update, $plugin_data, $plugin_file, $locales ) {
		if ( plugin_basename( TOURISM_CORE_FILE ) !== $plugin_file ) { return $update; }
		$release = $this->release();
		if ( empty( $release ) ) { return $update; }
		return array( 'slug' => 'tourism-core', 'version' => $release['version'], 'url' => self::URL,
			'package' => $release['package'], 'requires' => '5.8', 'requires_php' => '8.0' );
	}

	public function information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || 'tourism-core' !== ( $args->slug ?? '' ) ) { return $result; }
		$release = $this->release();
		if ( empty( $release ) ) { return $result; }
		return (object) array( 'name' => 'Tourism Core', 'slug' => 'tourism-core', 'author' => 'Humble Labz',
			'homepage' => self::URL, 'version' => $release['version'], 'requires' => '5.8', 'requires_php' => '8.0',
			'download_link' => $release['package'], 'external' => true,
			'sections' => array( 'description' => 'Tours, destinos, reservas y pagos para WordPress. Integración opcional con Astro.',
				'changelog' => '<a href="' . esc_url( self::URL . '/releases' ) . '">Ver las versiones publicadas en GitHub</a>' ) );
	}

	/** Send credentials only to our exact API asset endpoint, never to redirect hosts. */
	public function download( $reply, $package, $upgrader, $hook_extra = array() ) {
		if ( false !== $reply || ! is_string( $package ) || ! preg_match( '~^' . preg_quote( self::API, '~' ) . '/releases/assets/[1-9][0-9]*$~D', $package ) ) { return $reply; }
		if ( ! empty( $hook_extra['plugin'] ) && plugin_basename( TOURISM_CORE_FILE ) !== $hook_extra['plugin'] ) { return $reply; }
		$file = wp_tempnam( 'tourism-core.zip' );
		if ( ! $file ) { return new WP_Error( 'tourism_update_temp', 'No se pudo crear el archivo temporal de actualización.' ); }
		$response = wp_safe_remote_get( $package, array( 'headers' => $this->headers( 'application/octet-stream' ),
			'timeout' => 60, 'redirection' => 0, 'stream' => true, 'filename' => $file ) );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) { return $file; }
		wp_delete_file( $file );
		if ( ! is_wp_error( $response ) && 302 === wp_remote_retrieve_response_code( $response ) ) {
			$url = wp_remote_retrieve_header( $response, 'location' );
			$parts = wp_parse_url( $url );
			if ( is_array( $parts ) && 'https' === ( $parts['scheme'] ?? '' )
				&& in_array( $parts['host'] ?? '', array( 'release-assets.githubusercontent.com', 'objects.githubusercontent.com' ), true )
				&& empty( $parts['user'] ) && empty( $parts['pass'] ) && empty( $parts['port'] ) ) {
				return download_url( $url, 60 );
			}
		}
		return new WP_Error( 'tourism_update_download', 'No se pudo descargar Tourism Core. Comprueba el acceso a GitHub y el token de solo lectura si el repositorio es privado.' );
	}

	public function notice() {
		if ( ! current_user_can( 'update_plugins' ) || ! get_site_transient( 'tourism_core_update_error' ) ) { return; }
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network', 'update-core', 'update-core-network' ), true ) ) { return; }
		echo '<div class="notice notice-warning"><p>' . esc_html( 'Tourism Core: no se pudo consultar una versión publicada con tourism-core.zip en GitHub. Si el repositorio es privado, configura TOURISM_CORE_GITHUB_TOKEN en wp-config.php con acceso Contents: Read-only al repositorio. Consulta docs/updates.md del plugin.' ) . '</p></div>';
	}
}
