<?php
/**
 *  Contains functions required for the plugin.
 *
 * @package    Privacy_My_Way
 * @subpackage Plugin
 * @author     Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright  2017 Richard Coffee
 * @link       https://github.com/RichardCoffee/privacy-my-way/blob/master/functions.php
 */
defined( 'ABSPATH' ) || exit;

/**
 *  Class autoloader.
 *
 * @since 20170221
 * @param string $class  Name of requested class.
 */
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

/**
 *  Get plugin library class instance.
 *
 * @since 20170428
 * @param bool $force_log  Force the library to log the next entry.
 * @return object          The plugin library instance.
 */
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

