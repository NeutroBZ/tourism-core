<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Booking_Statuses {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'init', $this, 'register_statuses' );
	}

	public function register_statuses() {
		register_post_status(
			'booking_pending',
			array(
				'label'                     => _x( 'Pending', 'booking status', TOURISM_CORE_TD ),
				'public'                    => false,
				'internal'                  => true,
				'show_in_admin_all_list'     => true,
				'show_in_admin_status_list'  => true,
				'exclude_from_search'        => true,
				'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', TOURISM_CORE_TD ),
			)
		);

		register_post_status(
			'booking_confirmed',
			array(
				'label'                     => _x( 'Confirmed', 'booking status', TOURISM_CORE_TD ),
				'public'                    => false,
				'internal'                  => true,
				'show_in_admin_all_list'     => true,
				'show_in_admin_status_list'  => true,
				'exclude_from_search'        => true,
				'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', TOURISM_CORE_TD ),
			)
		);

		register_post_status(
			'booking_cancelled',
			array(
				'label'                     => _x( 'Cancelled', 'booking status', TOURISM_CORE_TD ),
				'public'                    => false,
				'internal'                  => true,
				'show_in_admin_all_list'     => true,
				'show_in_admin_status_list'  => true,
				'exclude_from_search'        => true,
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', TOURISM_CORE_TD ),
			)
		);
	}
}

