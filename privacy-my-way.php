<?php
/*
Plugin Name: Privacy My Way
Plugin URI: https://github.com/RichardCoffee/privacy-my-way
Description: Control what your WordPress site phones home about.  Does WordPress.org =really= need to know how many users you have?
Version: 1.0.0
Author: Richard Coffee
Author URI: richard.coffee@rtcenterprises.net
License: MIT
Text Domain: tcc-privacy
Domain Path: /locales
Tags: privacy, updates, plugins, themes, core, translations
*/

defined('ABSPATH') || exit;

define( 'TCC_PRIVACY_DIR', plugin_dir_path( __FILE__ ) );

require_once( 'functions.php' );

$plugin = TCC_Plugin_Privacy::get_instance( array( 'file' => __FILE__ ) );

register_activation_hook( __FILE__, array( 'TCC_Register_Privacy', 'activate' ) );
