<?php
/**
 * Privacy My Way
 *
 * @package   Privacy_My_Way
 * @author    Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright 2017 Richard Coffee
 * @license   MIT https://opensource.org/licenses/MIT
 * @link      https://github.com/RichardCoffee/privacy-my-way
 *
 * @wordpress-plugin
 * Plugin Name:       Privacy My Way
 * Plugin URI:        https://github.com/RichardCoffee/privacy-my-way
 * Description:       Control what your WordPress site phones home about.  Does WordPress.org =really= need to know how many users you have?
 * Version:           1.8.2
 * Requires at least: 4.7.0
 * Requires WP:       4.7.0
 * Tested up to:      5.4.2
 * Requires PHP:      5.3.6
 * Author:            Richard Coffee
 * Author URI:        http://rtcenterprises.net
 * GitHub Plugin URI: https://github.com/RichardCoffee/privacy-my-way
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       privacy-my-way
 * Domain Path:       /languages
 */

# https://github.com/helgatheviking/Nav-Menu-Roles/blob/master/nav-menu-roles.php
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'is_admin' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

define( 'PMW_PRIVACY_DIR', plugin_dir_path( __FILE__ ) );

require_once( 'functions.php' );

$pmw_plugin = PMW_Plugin_Privacy::get_instance( array( 'file' => __FILE__ ) );

register_activation_hook( __FILE__, array( 'PMW_Register_Privacy', 'activate' ) );
