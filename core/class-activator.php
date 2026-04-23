<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Activator {
	public static function activate() {
		// Ensure CPTs are registered before rewrite flush.
		if ( function_exists( 'tourism_core' ) ) {
			tourism_core()->register_for_activation();
		}
		flush_rewrite_rules();
	}
}

