<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Deactivator {
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

