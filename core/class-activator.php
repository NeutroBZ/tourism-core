<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Activator {
	public static function activate() {
		// Ensure CPTs are registered before rewrite flush.
		if ( function_exists( 'tourism_core' ) ) {
			tourism_core()->register_for_activation();
		} else {
			require_once TOURISM_CORE_PATH . 'includes/class-module-registry.php';
			require_once TOURISM_CORE_PATH . 'includes/class-config.php';
			require_once TOURISM_CORE_PATH . 'modules/site/class-site-module.php';

			$site = new Tourism_Core_Module_Site();
			$site->register_cpt();
			Tourism_Core_Module_Site::bootstrap_defaults();
		}
		flush_rewrite_rules();
	}
}
