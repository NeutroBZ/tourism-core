<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'modules/site/class-site-cpt.php';
require_once TOURISM_CORE_PATH . 'modules/site/class-site-admin.php';
require_once TOURISM_CORE_PATH . 'modules/site/class-site-metaboxes.php';

class Tourism_Core_Module_Site {
	public function register( Tourism_Core_Loader $loader ) {
		$cpt = new Tourism_Core_Site_CPT();
		$cpt->register( $loader );

		$admin = new Tourism_Core_Site_Admin();
		$admin->register( $loader );

		$metaboxes = new Tourism_Core_Site_Metaboxes();
		$metaboxes->register( $loader );

		$loader->add_action( 'init', $this, 'ensure_site_post', 5 );
	}

	public function ensure_site_post() {
		Tourism_Core_Config::instance()->ensure_site_post();
	}

	public function register_cpt() {
		$cpt = new Tourism_Core_Site_CPT();
		$cpt->register_cpt();
	}

	public static function bootstrap_defaults() {
		$config = Tourism_Core_Config::instance();
		if ( null === get_option( Tourism_Core_Config::OPTION_MODULES, null ) ) {
			update_option( Tourism_Core_Config::OPTION_MODULES, Tourism_Core_Module_Registry::default_modules(), false );
		}
		$config->ensure_site_post();
	}
}
