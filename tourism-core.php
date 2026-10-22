<?php
/**
 * Plugin Name: Tourism Core
 * Description: Core functionality (CPTs, shortcodes, templates) for tourism agency themes.
 * Version: 0.6.4
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Update URI: https://github.com/NeutroBZ/tourism-core
 * Author: Humble Labz
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: tourism-core
 * Domain Path: /languages
 */

/*
 * Copyright (C) 2026 Humble Labz
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * Tourism Core is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tourism Core is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tourism Core. If not, see https://www.gnu.org/licenses/.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config/constants.php';
require_once TOURISM_CORE_PATH . 'includes/class-github-updater.php';
( new Tourism_Core_GitHub_Updater() )->register();
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

