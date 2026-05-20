<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Config {
	const OPTION_SITE_ID  = 'tourism_core_site_id';
	const OPTION_MODULES  = 'tourism_core_modules';

	/** @var self|null */
	private static $instance = null;

	/** @var array<string, bool>|null */
	private $modules = null;

	/** @var int|null */
	private $site_post_id = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$stored = get_option( self::OPTION_MODULES, null );
		$this->modules = is_array( $stored )
			? Tourism_Core_Module_Registry::sanitize_modules( $stored )
			: Tourism_Core_Module_Registry::default_modules();
	}

	/**
	 * @return array<string, bool>
	 */
	public function get_modules() {
		return $this->modules;
	}

	public function is_module_enabled( $module ) {
		$module = sanitize_key( (string) $module );
		return ! empty( $this->modules[ $module ] );
	}

	/**
	 * @param array<string, bool> $modules
	 */
	public function set_modules( $modules ) {
		$this->modules = Tourism_Core_Module_Registry::sanitize_modules( $modules );
		update_option( self::OPTION_MODULES, $this->modules, false );
	}

	public function get_site_post_id() {
		if ( null !== $this->site_post_id ) {
			return $this->site_post_id;
		}

		$id = (int) get_option( self::OPTION_SITE_ID, 0 );
		if ( $id > 0 && 'tourism_site' === get_post_type( $id ) ) {
			$this->site_post_id = $id;
			return $id;
		}

		$this->site_post_id = $this->resolve_site_post_id();
		return $this->site_post_id;
	}

	public function set_site_post_id( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return;
		}
		$this->site_post_id = $post_id;
		update_option( self::OPTION_SITE_ID, $post_id, false );
	}

	/**
	 * @return array<string, string>
	 */
	public function get_agency() {
		$post_id = $this->get_site_post_id();
		if ( $post_id <= 0 ) {
			return $this->empty_agency();
		}

		return array(
			'name'      => (string) get_post_meta( $post_id, 'tourism_agency_name', true ),
			'phone'     => (string) get_post_meta( $post_id, 'tourism_agency_phone', true ),
			'whatsapp'  => (string) get_post_meta( $post_id, 'tourism_agency_whatsapp', true ),
			'email'     => (string) get_post_meta( $post_id, 'tourism_agency_email', true ),
			'address'   => (string) get_post_meta( $post_id, 'tourism_agency_address', true ),
			'facebook'  => (string) get_post_meta( $post_id, 'tourism_social_facebook', true ),
			'instagram' => (string) get_post_meta( $post_id, 'tourism_social_instagram', true ),
			'tiktok'    => (string) get_post_meta( $post_id, 'tourism_social_tiktok', true ),
			'hours'     => (string) get_post_meta( $post_id, 'tourism_agency_hours', true ),
		);
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function get_agency_field( $key ) {
		$key  = sanitize_key( (string) $key );
		$data = $this->get_agency();
		return isset( $data[ $key ] ) ? (string) $data[ $key ] : '';
	}

	/**
	 * @return array<string, string>
	 */
	private function empty_agency() {
		return array(
			'name'      => '',
			'phone'     => '',
			'whatsapp'  => '',
			'email'     => '',
			'address'   => '',
			'facebook'  => '',
			'instagram' => '',
			'tiktok'    => '',
			'hours'     => '',
		);
	}

	private function resolve_site_post_id() {
		$posts = get_posts(
			array(
				'post_type'      => 'tourism_site',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		if ( empty( $posts ) ) {
			return 0;
		}

		$id = (int) $posts[0];
		$this->set_site_post_id( $id );
		return $id;
	}

	/**
	 * Create the singleton site configuration post if missing.
	 *
	 * @return int Post ID or 0 on failure.
	 */
	public function ensure_site_post() {
		$existing = $this->get_site_post_id();
		if ( $existing > 0 ) {
			return $existing;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'tourism_site',
				'post_status' => 'publish',
				'post_title'  => __( 'Site configuration', TOURISM_CORE_TD ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		$this->set_site_post_id( (int) $post_id );
		return (int) $post_id;
	}
}
