<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'includes/class-metabox-helper.php';
require_once TOURISM_CORE_PATH . 'includes/trait-order-column.php';

use Tourism_Core_Metabox_Helper as Metabox_Helper;

/**
 * Shared base for metabox controller classes.
 */
abstract class Tourism_Core_Metabox_Base {

	/**
	 * Emit the main save nonce for a metabox screen.
	 *
	 * @param string $action Nonce action.
	 * @param string $field  Nonce field name.
	 */
	protected function emit_nonce( $action, $field ) {
		wp_nonce_field( $action, $field );
	}

	/**
	 * Require the main full-screen nonce + capability before saving.
	 *
	 * @param string $nonce_field Nonce field name.
	 * @param string $nonce_action Nonce action.
	 * @param int    $post_id Post ID.
	 * @param string $cap Capability.
	 * @return bool True when save may proceed.
	 */
	protected function guard_full_save( $nonce_field, $nonce_action, $post_id, $cap = 'edit_post' ) {
		if ( ! Metabox_Helper::verify_nonce( $nonce_field, $nonce_action ) ) {
			return false;
		}
		return Metabox_Helper::can_save( $post_id, $cap );
	}
}
