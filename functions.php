<?php

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

function pmw_library() {
	return new PMW_Plugin_Library;
}
