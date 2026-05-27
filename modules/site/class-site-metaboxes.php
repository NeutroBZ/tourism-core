<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Site_Metaboxes {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'add_meta_boxes', $this, 'add_meta_boxes' );
		$loader->add_action( 'save_post_tourism_site', $this, 'save', 10, 2 );
		$loader->add_action( 'admin_notices', $this, 'modules_saved_notice' );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'tourism-core-site-modules',
			__( 'Enabled modules', TOURISM_CORE_TD ),
			array( $this, 'render_modules' ),
			'tourism_site',
			'normal',
			'high'
		);

		add_meta_box(
			'tourism-core-site-agency',
			__( 'Agency details', TOURISM_CORE_TD ),
			array( $this, 'render_agency' ),
			'tourism_site',
			'normal',
			'default'
		);

		add_meta_box(
			'tourism-core-site-social',
			__( 'Social networks', TOURISM_CORE_TD ),
			array( $this, 'render_social' ),
			'tourism_site',
			'normal',
			'default'
		);

		add_meta_box(
			'tourism-core-site-hours',
			__( 'Business hours', TOURISM_CORE_TD ),
			array( $this, 'render_hours' ),
			'tourism_site',
			'normal',
			'default'
		);
	}

	public function render_modules( $post ) {
		wp_nonce_field( 'tourism_core_save_site', 'tourism_core_site_nonce' );

		$modules = Tourism_Core_Config::instance()->get_modules();
		$registry = Tourism_Core_Module_Registry::all();

		$template = TOURISM_CORE_PATH . 'modules/site/templates/modules-metabox.php';
		if ( is_readable( $template ) ) {
			$tourism_core_site_modules = $modules;
			$tourism_core_module_registry = $registry;
			include $template;
		}
	}

	public function render_agency( $post ) {
		wp_nonce_field( 'tourism_core_save_site', 'tourism_core_site_nonce' );

		$tourism_core_site_agency = Tourism_Core_Config::instance()->get_agency();

		$template = TOURISM_CORE_PATH . 'modules/site/templates/agency-metabox.php';
		if ( is_readable( $template ) ) {
			include $template;
		}
	}

	public function render_social( $post ) {
		wp_nonce_field( 'tourism_core_save_site', 'tourism_core_site_nonce' );

		$tourism_core_site_agency = Tourism_Core_Config::instance()->get_agency();

		$template = TOURISM_CORE_PATH . 'modules/site/templates/social-metabox.php';
		if ( is_readable( $template ) ) {
			include $template;
		}
	}

	public function render_hours( $post ) {
		wp_nonce_field( 'tourism_core_save_site', 'tourism_core_site_nonce' );

		$tourism_core_site_agency = Tourism_Core_Config::instance()->get_agency();

		$template = TOURISM_CORE_PATH . 'modules/site/templates/hours-metabox.php';
		if ( is_readable( $template ) ) {
			include $template;
		}
	}

	/**
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['tourism_core_site_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tourism_core_site_nonce'] ) ), 'tourism_core_save_site' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( 'tourism_site' !== $post->post_type ) {
			return;
		}

		$site_id = tourism_core_get_site_post_id();
		if ( $site_id > 0 && (int) $post_id !== $site_id ) {
			return;
		}

		if ( isset( $_POST['tourism_site_agency_metabox_present'] ) ) {
			update_post_meta( $post_id, 'tourism_agency_company_name', isset( $_POST['tourism_agency_company_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_company_name'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_commercial_name', isset( $_POST['tourism_agency_commercial_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_commercial_name'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_ruc', isset( $_POST['tourism_agency_ruc'] ) ? $this->sanitize_ruc( wp_unslash( $_POST['tourism_agency_ruc'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_name', isset( $_POST['tourism_agency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_name'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_phone', isset( $_POST['tourism_agency_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_phone'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_whatsapp', isset( $_POST['tourism_agency_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_whatsapp'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_email', isset( $_POST['tourism_agency_email'] ) ? sanitize_email( wp_unslash( $_POST['tourism_agency_email'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_address', isset( $_POST['tourism_agency_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tourism_agency_address'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_google_maps', isset( $_POST['tourism_agency_google_maps'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_agency_google_maps'] ) ) : '' );

			$custom_agency = array();
			if ( isset( $_POST['tourism_agency_custom'] ) && is_array( $_POST['tourism_agency_custom'] ) ) {
				foreach ( wp_unslash( $_POST['tourism_agency_custom'] ) as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}

					$label = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '';
					$value = isset( $item['value'] ) ? sanitize_text_field( $item['value'] ) : '';

					if ( '' === $label && '' === $value ) {
						continue;
					}

					$custom_agency[] = array(
						'label' => $label,
						'value' => $value,
					);
				}
			}
			update_post_meta( $post_id, 'tourism_agency_custom', $custom_agency );
		}

		if ( isset( $_POST['tourism_site_social_metabox_present'] ) ) {
			update_post_meta( $post_id, 'tourism_social_facebook', isset( $_POST['tourism_social_facebook'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_facebook'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_social_instagram', isset( $_POST['tourism_social_instagram'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_instagram'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_social_tripadvisor', isset( $_POST['tourism_social_tripadvisor'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_tripadvisor'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_social_linkedin', isset( $_POST['tourism_social_linkedin'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_linkedin'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_social_extra', isset( $_POST['tourism_social_extra'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tourism_social_extra'] ) ) : '' );

			$custom_social = array();
			if ( isset( $_POST['tourism_social_custom'] ) && is_array( $_POST['tourism_social_custom'] ) ) {
				foreach ( wp_unslash( $_POST['tourism_social_custom'] ) as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}

					$label = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : '';
					$url   = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';

					if ( '' === $label && '' === $url ) {
						continue;
					}

					$custom_social[] = array(
						'label' => $label,
						'url'   => $url,
					);
				}
			}
			update_post_meta( $post_id, 'tourism_social_custom', $custom_social );
		}

		if ( isset( $_POST['tourism_site_hours_metabox_present'] ) ) {
			$allowed_days = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
			$days_raw     = isset( $_POST['tourism_agency_hours_days'] ) && is_array( $_POST['tourism_agency_hours_days'] )
				? wp_unslash( $_POST['tourism_agency_hours_days'] )
				: array();

			$days = array();
			foreach ( $days_raw as $day ) {
				$day = sanitize_key( (string) $day );
				if ( in_array( $day, $allowed_days, true ) ) {
					$days[] = $day;
				}
			}
			$days = array_values( array_unique( $days ) );

			$open  = $this->sanitize_hours_time_input( isset( $_POST['tourism_agency_hours_open'] ) ? wp_unslash( $_POST['tourism_agency_hours_open'] ) : array(), array( 'hour' => '9', 'minute' => '00', 'ampm' => 'AM' ) );
			$close = $this->sanitize_hours_time_input( isset( $_POST['tourism_agency_hours_close'] ) ? wp_unslash( $_POST['tourism_agency_hours_close'] ) : array(), array( 'hour' => '6', 'minute' => '00', 'ampm' => 'PM' ) );

			$schedule = array(
				'days'  => $days,
				'open'  => $open,
				'close' => $close,
			);
			update_post_meta( $post_id, 'tourism_agency_hours_schedule', $schedule );

			$hours_text = isset( $_POST['tourism_agency_hours'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tourism_agency_hours'] ) ) : '';
			if ( '' === trim( $hours_text ) ) {
				$hours_text = $this->build_hours_summary( $schedule );
			}
			update_post_meta( $post_id, 'tourism_agency_hours', $hours_text );
		}

		if ( isset( $_POST['tourism_site_modules_metabox_present'] ) ) {
			$raw     = isset( $_POST['tourism_core_modules'] ) && is_array( $_POST['tourism_core_modules'] )
				? wp_unslash( $_POST['tourism_core_modules'] )
				: array();
			$modules = Tourism_Core_Module_Registry::sanitize_modules( $raw );
			Tourism_Core_Config::instance()->set_modules( $modules );
			set_transient( 'tourism_core_modules_saved', 1, 30 );
		}
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function sanitize_ruc( $value ) {
		$digits = preg_replace( '/\D/', '', (string) $value );
		return substr( $digits, 0, 20 );
	}

	/**
	 * @param mixed $raw
	 * @param array{hour:string,minute:string,ampm:string} $default
	 * @return array{hour:string,minute:string,ampm:string}
	 */
	private function sanitize_hours_time_input( $raw, $default ) {
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
	 * @param array{days:array<int,string>,open:array{hour:string,minute:string,ampm:string},close:array{hour:string,minute:string,ampm:string}} $schedule
	 */
	private function build_hours_summary( $schedule ) {
		$day_labels = array(
			'mon' => 'Mon',
			'tue' => 'Tue',
			'wed' => 'Wed',
			'thu' => 'Thu',
			'fri' => 'Fri',
			'sat' => 'Sat',
			'sun' => 'Sun',
		);

		$days = array();
		foreach ( $schedule['days'] as $day ) {
			if ( isset( $day_labels[ $day ] ) ) {
				$days[] = $day_labels[ $day ];
			}
		}

		if ( empty( $days ) ) {
			return '';
		}

		$open  = $schedule['open']['hour'] . ':' . $schedule['open']['minute'] . ' ' . $schedule['open']['ampm'];
		$close = $schedule['close']['hour'] . ':' . $schedule['close']['minute'] . ' ' . $schedule['close']['ampm'];

		return implode( ', ', $days ) . ' - ' . $open . ' to ' . $close;
	}

	public function modules_saved_notice() {
		if ( ! get_transient( 'tourism_core_modules_saved' ) ) {
			return;
		}
		delete_transient( 'tourism_core_modules_saved' );

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'tourism_site' !== $screen->post_type ) {
			return;
		}

		echo '<div class="notice notice-info is-dismissible"><p>';
		echo esc_html__( 'Module settings were saved. If you disabled a module, its menu may disappear after you reload this page.', TOURISM_CORE_TD );
		echo '</p></div>';
	}
}
