<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Frontend_Assets {
	public function register( Tourism_Core_Loader $loader ) {
		$loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue' );
	}

	public function enqueue() {
		wp_register_style(
			'tourism-core-frontend',
			TOURISM_CORE_URL . 'assets/css/frontend.css',
			array(),
			TOURISM_CORE_VERSION
		);
		wp_enqueue_style( 'tourism-core-frontend' );
	}
}

