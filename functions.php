<?php


function tcc_privacy_class_loader( $class ) {
	if ( substr( $class, 0, 4 ) === 'TCC_' ) {
		$load = str_replace( '_', '/', substr( $class, ( strpos( $class, '_' ) + 1 ) ) );
		$stem = "/classes/$load.php"
		$file = TCC_PRIVACY_DIR . $stem;
		if ( is_readable( $file ) ) {
			include $file;
		}
	}
}
spl_autoload_register( 'tcc_privacy_class_loader' ); //*/
