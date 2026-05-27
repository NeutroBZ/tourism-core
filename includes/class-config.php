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
	 * @return array<string, mixed>
	 */
	public function get_agency() {
		$post_id = $this->get_site_post_id();
		if ( $post_id <= 0 ) {
			return $this->empty_agency();
		}

		return array(
			'company_name'    => (string) get_post_meta( $post_id, 'tourism_agency_company_name', true ),
			'commercial_name' => (string) get_post_meta( $post_id, 'tourism_agency_commercial_name', true ),
			'ruc'             => (string) get_post_meta( $post_id, 'tourism_agency_ruc', true ),
			'name'            => (string) get_post_meta( $post_id, 'tourism_agency_name', true ),
			'phone'           => (string) get_post_meta( $post_id, 'tourism_agency_phone', true ),
			'whatsapp'        => (string) get_post_meta( $post_id, 'tourism_agency_whatsapp', true ),
			'email'           => (string) get_post_meta( $post_id, 'tourism_agency_email', true ),
			'address'         => (string) get_post_meta( $post_id, 'tourism_agency_address', true ),
			'google_maps'     => (string) get_post_meta( $post_id, 'tourism_agency_google_maps', true ),
			'agency_custom'   => $this->get_agency_custom( $post_id ),
			'facebook'        => (string) get_post_meta( $post_id, 'tourism_social_facebook', true ),
			'instagram'       => (string) get_post_meta( $post_id, 'tourism_social_instagram', true ),
			'tripadvisor'     => (string) get_post_meta( $post_id, 'tourism_social_tripadvisor', true ),
			'linkedin'        => (string) get_post_meta( $post_id, 'tourism_social_linkedin', true ),
			'social_extra'    => (string) get_post_meta( $post_id, 'tourism_social_extra', true ),
			'social_custom'   => $this->get_social_custom( $post_id ),
			'hours'           => (string) get_post_meta( $post_id, 'tourism_agency_hours', true ),
			'hours_schedule'  => $this->get_hours_schedule( $post_id ),
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
	 * @return array<string, mixed>
	 */
	private function empty_agency() {
		return array(
			'company_name'    => '',
			'commercial_name' => '',
			'ruc'             => '',
			'name'            => '',
			'phone'           => '',
			'whatsapp'        => '',
			'email'           => '',
			'address'         => '',
			'google_maps'     => '',
			'agency_custom'   => array(),
			'facebook'        => '',
			'instagram'       => '',
			'tripadvisor'     => '',
			'linkedin'        => '',
			'social_extra'    => '',
			'social_custom'   => array(),
			'hours'           => '',
			'hours_schedule'  => $this->default_hours_schedule(),
		);
	}

	/**
	 * @return array{days:array<int,string>,open:array{hour:string,minute:string,ampm:string},close:array{hour:string,minute:string,ampm:string}}
	 */
	private function default_hours_schedule() {
		return array(
			'days'  => array(),
			'open'  => array(
				'hour'   => '9',
				'minute' => '00',
				'ampm'   => 'AM',
			),
			'close' => array(
				'hour'   => '6',
				'minute' => '00',
				'ampm'   => 'PM',
			),
		);
	}

	/**
	 * @return array{days:array<int,string>,open:array{hour:string,minute:string,ampm:string},close:array{hour:string,minute:string,ampm:string}}
	 */
	private function get_hours_schedule( $post_id ) {
		$defaults = $this->default_hours_schedule();
		$raw      = get_post_meta( $post_id, 'tourism_agency_hours_schedule', true );
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		$allowed_days = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
		$days         = array();
		if ( isset( $raw['days'] ) && is_array( $raw['days'] ) ) {
			foreach ( $raw['days'] as $day ) {
				$day = sanitize_key( (string) $day );
				if ( in_array( $day, $allowed_days, true ) ) {
					$days[] = $day;
				}
			}
			$days = array_values( array_unique( $days ) );
		}

		$open  = $this->sanitize_schedule_time( isset( $raw['open'] ) ? $raw['open'] : array(), $defaults['open'] );
		$close = $this->sanitize_schedule_time( isset( $raw['close'] ) ? $raw['close'] : array(), $defaults['close'] );

		return array(
			'days'  => $days,
			'open'  => $open,
			'close' => $close,
		);
	}

	/**
	 * @param mixed $raw
	 * @param array{hour:string,minute:string,ampm:string} $default
	 * @return array{hour:string,minute:string,ampm:string}
	 */
	private function sanitize_schedule_time( $raw, $default ) {
		$raw = is_array( $raw ) ? $raw : array();

		$hour   = isset( $raw['hour'] ) ? (string) $raw['hour'] : $default['hour'];
		$minute = isset( $raw['minute'] ) ? (string) $raw['minute'] : $default['minute'];
		$ampm   = isset( $raw['ampm'] ) ? strtoupper( (string) $raw['ampm'] ) : $default['ampm'];

		$hour_int = (int) $hour;
		if ( $hour_int < 1 || $hour_int > 12 ) {
			$hour_int = (int) $default['hour'];
		}

		$allowed_minutes = array( '00', '15', '30', '45' );
		if ( ! in_array( $minute, $allowed_minutes, true ) ) {
			$minute = $default['minute'];
		}

		if ( ! in_array( $ampm, array( 'AM', 'PM' ), true ) ) {
			$ampm = $default['ampm'];
		}

		return array(
			'hour'   => (string) $hour_int,
			'minute' => $minute,
			'ampm'   => $ampm,
		);
	}

	/**
	 * @return array<int, array{label:string,value:string}>
	 */
	private function get_agency_custom( $post_id ) {
		$items = get_post_meta( $post_id, 'tourism_agency_custom', true );
		if ( ! is_array( $items ) ) {
			return array();
		}

		$out = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';
			$value = isset( $item['value'] ) ? sanitize_text_field( (string) $item['value'] ) : '';
			if ( '' === $label && '' === $value ) {
				continue;
			}

			$out[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $out;
	}

	/**
	 * @return array<int, array{label:string,url:string}>
	 */
	private function get_social_custom( $post_id ) {
		$items = get_post_meta( $post_id, 'tourism_social_custom', true );
		if ( ! is_array( $items ) ) {
			return array();
		}

		$out = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$label = isset( $item['label'] ) ? sanitize_text_field( (string) $item['label'] ) : '';
			$url   = isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';
			if ( '' === $label && '' === $url ) {
				continue;
			}

			$out[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}

		return $out;
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
