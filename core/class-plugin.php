<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'frontend/class-assets.php';
require_once TOURISM_CORE_PATH . 'frontend/class-shortcodes.php';
require_once TOURISM_CORE_PATH . 'modules/tours/class-tour-cpt.php';
require_once TOURISM_CORE_PATH . 'modules/destinations/class-destination-cpt.php';
require_once TOURISM_CORE_PATH . 'modules/emails/class-email-manager.php';
require_once TOURISM_CORE_PATH . 'modules/bookings/class-bookings-module.php';
require_once TOURISM_CORE_PATH . 'admin/class-admin.php';
require_once TOURISM_CORE_PATH . 'helpers/helpers-tours.php';

class Tourism_Core_Plugin {
	/** @var self|null */
	private static $instance = null;

	/** @var Tourism_Core_Loader */
	private $loader;

	/** @var Tourism_Core_Module_Tours */
	private $tours;

	/** @var Tourism_Core_Module_Destinations */
	private $destinations;

	/** @var Tourism_Core_Frontend_Assets */
	private $assets;

	/** @var Tourism_Core_Frontend_Shortcodes */
	private $shortcodes;

	/** @var Tourism_Core_Email_Manager */
	private $emails;

	/** @var Tourism_Core_Module_Bookings */
	private $bookings;

	/** @var Tourism_Core_Admin */
	private $admin;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->loader = new Tourism_Core_Loader();
	}

	public function register_core() {
		$this->tours = new Tourism_Core_Module_Tours();
		$this->tours->register( $this->loader );

		$this->destinations = new Tourism_Core_Module_Destinations();
		$this->destinations->register( $this->loader );

		$this->emails   = new Tourism_Core_Email_Manager();
		$this->bookings = new Tourism_Core_Module_Bookings( $this->emails );
		$this->bookings->register( $this->loader );

		$this->assets     = new Tourism_Core_Frontend_Assets();
		$this->shortcodes = new Tourism_Core_Frontend_Shortcodes();

		$this->assets->register( $this->loader );
		$this->shortcodes->register( $this->loader );

		if ( is_admin() ) {
			$this->admin = new Tourism_Core_Admin();
			$this->admin->register( $this->loader );
		}
	}

	public function register_for_activation() {
		$tours = new Tourism_Core_Module_Tours();
		$tours->register_cpt();
		$tours->register_meta();

		$dest = new Tourism_Core_Module_Destinations();
		$dest->register_cpt();

		$emails   = new Tourism_Core_Email_Manager();
		$bookings = new Tourism_Core_Module_Bookings( $emails );
		$bookings->register_for_activation();
	}

	public function run() {
		$this->loader->add_action( 'init', $this, 'load_textdomain', 1 );

		$this->register_core();

		$this->loader->run();
	}

	public function load_textdomain() {
		load_plugin_textdomain( TOURISM_CORE_TD, false, dirname( plugin_basename( TOURISM_CORE_FILE ) ) . '/languages' );
	}
}

