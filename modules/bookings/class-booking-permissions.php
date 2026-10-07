<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Booking_Permissions {
	const CAP = 'manage_tourism_bookings';

	public static function upgrade() {
		if ( '1' === get_option( 'tourism_core_booking_permissions_version' ) ) {
			return;
		}
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( self::CAP );
			update_option( 'tourism_core_booking_permissions_version', '1', false );
		}
	}

	public static function capabilities() {
		return array(
			'edit_posts' => self::CAP,
			'edit_others_posts' => self::CAP,
			'edit_private_posts' => self::CAP,
			'edit_published_posts' => self::CAP,
			'publish_posts' => self::CAP,
			'read_private_posts' => self::CAP,
			'delete_posts' => self::CAP,
			'delete_others_posts' => self::CAP,
			'delete_private_posts' => self::CAP,
			'delete_published_posts' => self::CAP,
			'create_posts' => 'do_not_allow',
		);
	}

	public static function restrict_meta_caps( $caps, $cap, $user_id, $args ) {
		if ( in_array( $cap, array( 'read_post', 'edit_post', 'delete_post', 'read_tourism_booking', 'edit_tourism_booking', 'delete_tourism_booking' ), true ) && isset( $args[0] ) && 'booking' === get_post_type( $args[0] ) ) {
			$caps[] = self::CAP;
		}
		return array_unique( $caps );
	}
}
