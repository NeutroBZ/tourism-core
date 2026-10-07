<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Deactivator {
	public static function deactivate() {
		wp_unschedule_hook( 'tourism_core_retry_booking_email' );
		wp_clear_scheduled_hook( 'tourism_core_cleanup_locks' );
		flush_rewrite_rules();
	}
}

