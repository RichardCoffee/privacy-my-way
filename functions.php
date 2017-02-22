<?php

/*
function tcc_privacy_class_loader( $class ) {
	if ( substr( $class, 0, 4 ) === 'TCC_' ) {
		$load = str_replace( '_', '/', substr( $class, ( strpos( $class, '_' ) + 1 ) ) );
		$stem = "/classes/$load.php"
		$file = TCC_PRIVACY_DIR . $stem;
		if ( is_readable( $file ) ) {
			include $file;
#		} else if ( defined( 'FLUIDITY_THEME_DIR' ) ) {
#			$file = FLUIDITY_THEME_DIR . $stem;
#			if ( is_readable( $file ) ) {
#				include $file;
#			}
		}
	}
}
spl_autoload_register( 'tcc_privacy_class_loader' ); //*/

#	note:  used instead of class loader function above
if ( ! class_exists( 'TCC_Plugin_Paths' )     { require_once( 'classes/Plugin/Paths.php' ); }
if ( ! class_exists( 'TCC_Plugin_Basic' )     { require_once( 'classes/Plugin/Basic.php' ); }
if ( ! class_exists( 'TCC_Plugin_Privacy' )   { require_once( 'classes/Plugin/Privacy.php' ); }
if ( ! class_exists( 'TCC_Register_Plugin' )  { require_once( 'classes/Register/Plugin.php' ); }
if ( ! class_exists( 'TCC_Register_Privacy' ) { require_once( 'classes/Register/Privacy.php' ); }
