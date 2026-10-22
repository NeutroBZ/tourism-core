<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TOURISM_CORE_VERSION', '0.6.0' );
define( 'TOURISM_CORE_FILE', __DIR__ . '/../tourism-core.php' );
define( 'TOURISM_CORE_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) . '../' );
define( 'TOURISM_CORE_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) . '../' );

define( 'TOURISM_CORE_TD', 'tourism-core' );
define( 'TOURISM_CORE_TEMPLATES_DIR', TOURISM_CORE_PATH . 'templates/' );
define( 'TOURISM_CORE_BRAND_ICON', TOURISM_CORE_URL . 'assets/brand/humble-labz-menu-icon.svg' );

