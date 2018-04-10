<?php

defined( 'ABSPATH' ) || exit;

include_once( 'includes/debugging.php' );

function pmw_privacy_class_loader( $class ) {
	if ( substr( $class, 0, 4 ) === 'PMW_' ) {
		$load = str_replace( '_', '/', substr( $class, ( strpos( $class, '_' ) + 1 ) ) );
		$file = PMW_PRIVACY_DIR . '/classes/' . $load . '.php';
		if ( is_readable( $file ) ) {
			include $file;
		}
	}
}
spl_autoload_register( 'pmw_privacy_class_loader' ); //*/

function pmw( $force_log = false ) {
	static $library;
	if ( empty( $library ) ) {
		$library = new PMW_Plugin_Library;
	}
	if ( $force_log ) {
		$library->logging_force = true;
	}
	return $library;
}

# http://stackoverflow.com/questions/14348470/is-ajax-in-wordpress
if ( ! function_exists( 'is_ajax' ) ) {
	function is_ajax() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? true : false;
	}
}
