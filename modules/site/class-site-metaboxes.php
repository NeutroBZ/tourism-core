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
			update_post_meta( $post_id, 'tourism_agency_name', isset( $_POST['tourism_agency_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_name'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_phone', isset( $_POST['tourism_agency_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_phone'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_whatsapp', isset( $_POST['tourism_agency_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['tourism_agency_whatsapp'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_email', isset( $_POST['tourism_agency_email'] ) ? sanitize_email( wp_unslash( $_POST['tourism_agency_email'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_address', isset( $_POST['tourism_agency_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tourism_agency_address'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_agency_google_maps', isset( $_POST['tourism_agency_google_maps'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_agency_google_maps'] ) ) : '' );
		}

		if ( isset( $_POST['tourism_site_social_metabox_present'] ) ) {
			update_post_meta( $post_id, 'tourism_social_facebook', isset( $_POST['tourism_social_facebook'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_facebook'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_social_instagram', isset( $_POST['tourism_social_instagram'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_instagram'] ) ) : '' );
			update_post_meta( $post_id, 'tourism_social_tiktok', isset( $_POST['tourism_social_tiktok'] ) ? esc_url_raw( wp_unslash( $_POST['tourism_social_tiktok'] ) ) : '' );
		}

		if ( isset( $_POST['tourism_site_hours_metabox_present'] ) ) {
			update_post_meta( $post_id, 'tourism_agency_hours', isset( $_POST['tourism_agency_hours'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tourism_agency_hours'] ) ) : '' );
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
