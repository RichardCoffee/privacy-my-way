<?php

include_once( 'debugging.php' );

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

if ( ! function_exists( 'pmw_privacy' ) ) {
	function pmw_privacy( $option ) {
		static $data;
		$request = '';
		if ( empty( $data ) ) {
			$data = get_option( 'tcc_options_privacy' );
		}
		if ( isset( $data[ $option ] ) ) {
			$request = $data[ $option ];
		}
		return $request;
	}
}
