<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tourism_Core_Template {
	/**
	 * Render a template with theme override support.
	 *
	 * Theme override path: wp-content/themes/<theme>/tourism-core/<template>
	 *
	 * @param string $template
	 * @param array  $vars
	 * @return string
	 */
	public static function render( $template, $vars = array() ) {
		$template = ltrim( (string) $template, '/\\' );
		$vars     = is_array( $vars ) ? $vars : array();

		$theme_path = trailingslashit( get_stylesheet_directory() ) . 'tourism-core/' . $template;
		$path       = file_exists( $theme_path ) ? $theme_path : TOURISM_CORE_TEMPLATES_DIR . $template;

		if ( ! file_exists( $path ) ) {
			return '';
		}

		ob_start();
		extract( $vars, EXTR_SKIP );
		include $path;
		return (string) ob_get_clean();
	}
}

