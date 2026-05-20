<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Site_Admin {
	const MENU_SLUG = 'tourism-core';

	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'admin_menu', $this, 'register_menu', 9 );
		$loader->add_action( 'admin_init', $this, 'redirect_configuration_screens' );
		$loader->add_action( 'admin_head', $this, 'hide_add_new_button' );
		$loader->add_filter( 'post_row_actions', $this, 'remove_row_actions', 10, 2 );
		$loader->add_filter( 'pre_delete_post', $this, 'prevent_delete', 10, 3 );
		$loader->add_filter( 'parent_file', $this, 'highlight_menu_parent' );
		$loader->add_filter( 'submenu_file', $this, 'highlight_menu_submenu' );
		$loader->add_action( 'admin_head', $this, 'hide_title_editor' );
		$loader->add_filter( 'manage_tourism_site_posts_columns', $this, 'simplify_columns' );
		$loader->add_action( 'load-post.php', $this, 'restrict_edit_screen' );
		$loader->add_action( 'load-post-new.php', $this, 'block_new_screen' );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Tourism Core', TOURISM_CORE_TD ),
			__( 'Tourism Core', TOURISM_CORE_TD ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_menu_landing' ),
			TOURISM_CORE_URL . 'assets/ico/admin-digixonic.svg',
			3
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Configuration', TOURISM_CORE_TD ),
			__( 'Configuration', TOURISM_CORE_TD ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_menu_landing' )
		);
	}

	public function render_menu_landing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', TOURISM_CORE_TD ) );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Tourism Core', TOURISM_CORE_TD ) . '</h1>';
		echo '<p>' . esc_html__( 'Could not load site configuration. Try deactivating and reactivating the plugin.', TOURISM_CORE_TD ) . '</p></div>';
	}

	/**
	 * Redirect before HTML output. Must run on admin_init (not the menu page callback).
	 */
	public function redirect_configuration_screens() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$url = tourism_core_get_site_settings_url();
		if ( ! $url ) {
			return;
		}

		global $pagenow;

		if ( isset( $_GET['page'] ) && self::MENU_SLUG === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_safe_redirect( $url );
			exit;
		}

		if ( 'edit.php' === $pagenow ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
			if ( 'tourism_site' === $post_type ) {
				wp_safe_redirect( $url );
				exit;
			}
		}
	}

	public function hide_add_new_button() {
		global $typenow;
		if ( 'tourism_site' !== $typenow ) {
			return;
		}
		echo '<style>.page-title-action{display:none !important;}</style>';
	}

	/**
	 * @param array<string, string> $actions
	 * @param WP_Post               $post
	 * @return array<string, string>
	 */
	public function remove_row_actions( $actions, $post ) {
		if ( ! ( $post instanceof WP_Post ) || 'tourism_site' !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'], $actions['trash'] );
		return $actions;
	}

	/**
	 * @param bool|null $delete
	 * @param WP_Post   $post
	 * @param bool      $force_delete
	 * @return bool|null
	 */
	public function prevent_delete( $delete, $post, $force_delete ) {
		unset( $force_delete );
		if ( $post instanceof WP_Post && 'tourism_site' === $post->post_type ) {
			return false;
		}
		return $delete;
	}

	public function highlight_menu_parent( $parent_file ) {
		global $post;
		if ( $post instanceof WP_Post && 'tourism_site' === $post->post_type ) {
			return self::MENU_SLUG;
		}
		return $parent_file;
	}

	public function highlight_menu_submenu( $submenu_file ) {
		global $post;
		if ( $post instanceof WP_Post && 'tourism_site' === $post->post_type ) {
			return self::MENU_SLUG;
		}
		return $submenu_file;
	}

	public function hide_title_editor() {
		global $post;
		if ( ! ( $post instanceof WP_Post ) || 'tourism_site' !== $post->post_type ) {
			return;
		}
		echo '<style>#titlediv{display:none;}</style>';
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function simplify_columns( $columns ) {
		return array(
			'cb'    => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox" />',
			'title' => __( 'Configuration', TOURISM_CORE_TD ),
		);
	}

	public function restrict_edit_screen() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		if ( $post_id <= 0 || 'tourism_site' !== get_post_type( $post_id ) ) {
			return;
		}

		$site_id = tourism_core_get_site_post_id();
		if ( $site_id > 0 && $post_id !== $site_id ) {
			wp_safe_redirect( get_edit_post_link( $site_id, 'raw' ) );
			exit;
		}
	}

	public function block_new_screen() {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		if ( 'tourism_site' === $post_type || ( isset( $_GET['post'] ) && 'tourism_site' === get_post_type( (int) $_GET['post'] ) ) ) {
			wp_safe_redirect( tourism_core_get_site_settings_url() );
			exit;
		}
	}
}
