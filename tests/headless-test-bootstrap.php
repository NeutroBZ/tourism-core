<?php
/** Override the headless module for this CLI process only, without changing saved settings. */
if ( 'cli' !== PHP_SAPI ) { exit; }
$headless_test_option = static function ( $modules ) {
	$modules = is_array( $modules ) ? $modules : array( 'tours' => true, 'destinations' => true, 'bookings' => true );
	if ( 'legacy' === TOURISM_TEST_HEADLESS_MODE ) {
		unset( $modules['headless'] );
	} else {
		$modules['headless'] = 'enabled' === TOURISM_TEST_HEADLESS_MODE;
	}
	return $modules;
};
foreach ( array( 'option_tourism_core_modules', 'default_option_tourism_core_modules' ) as $headless_test_hook ) {
	$GLOBALS['wp_filter'][ $headless_test_hook ][10][] = array( 'function' => $headless_test_option, 'accepted_args' => 1 );
}
