<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TOURISM_CORE_PATH . 'modules/bookings/class-booking-statuses.php';
require_once TOURISM_CORE_PATH . 'modules/bookings/class-booking-permissions.php';
require_once TOURISM_CORE_PATH . 'modules/bookings/class-booking-validator.php';
require_once TOURISM_CORE_PATH . 'modules/bookings/class-booking-guard.php';
require_once TOURISM_CORE_PATH . 'modules/bookings/class-booking-cpt.php';
require_once TOURISM_CORE_PATH . 'modules/bookings/class-booking-handler.php';

class Tourism_Core_Module_Bookings {
	/** @var Tourism_Core_Email_Manager */
	private $emails;

	public function __construct( $emails ) {
		$this->emails = $emails;
	}

	public function register( Tourism_Core_Loader $loader ) {
		$permissions = new Tourism_Core_Booking_Permissions();
		$loader->add_action( 'init', $permissions, 'upgrade', 1 );
		$loader->add_filter( 'map_meta_cap', $permissions, 'restrict_meta_caps', 10, 4 );
		$statuses = new Tourism_Core_Booking_Statuses();
		$statuses->register( $loader );

		$cpt = new Tourism_Core_Booking_CPT();
		$cpt->register( $loader );

		$handler = new Tourism_Core_Booking_Handler( $this->emails );
		$handler->register( $loader );
	}

	public function register_for_activation() {
		Tourism_Core_Booking_Permissions::upgrade();
		$statuses = new Tourism_Core_Booking_Statuses();
		$statuses->register_statuses();

		$cpt = new Tourism_Core_Booking_CPT();
		$cpt->register_cpt();
		$cpt->register_meta();
	}
}

