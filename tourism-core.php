<?php
/**
 * Plugin Name: Tourism Core
 * Description: Core functionality (CPTs, shortcodes, templates) for tourism agency themes.
 * Version: 0.3.0
 * Author: jcortez
 * Text Domain: tourism-core
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config/constants.php';
require_once TOURISM_CORE_PATH . 'includes/class-metabox-helper.php';
require_once TOURISM_CORE_PATH . 'includes/trait-order-column.php';
require_once TOURISM_CORE_PATH . 'includes/class-metabox-base.php';

require_once TOURISM_CORE_PATH . 'core/class-loader.php';
require_once TOURISM_CORE_PATH . 'core/class-activator.php';
require_once TOURISM_CORE_PATH . 'core/class-deactivator.php';
require_once TOURISM_CORE_PATH . 'core/class-plugin.php';

register_activation_hook( __FILE__, array( 'Tourism_Core_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Tourism_Core_Deactivator', 'deactivate' ) );

function tourism_core() {
	return Tourism_Core_Plugin::instance();
}

add_action(
	'plugins_loaded',
	static function () {
		tourism_core()->run();
	}
);

