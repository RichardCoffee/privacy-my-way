<?php

defined( 'ABSPATH' ) || exit;

class PMW_Register_Register {

	private   static $dep_func = 'tcc_enqueue';  // FIXME:  not a good value to check for theme dependency on.
	protected static $options  = 'about';
	private   static $our_site = '<a href="the-creative-collective.com" target="tcc">%s</a>';
	protected static $prefix   = 'tcc_options_';  #  Option slug prefix

	private   static $rc_email = '<a href="mailto:richard.coffee@gmail.com">%s</a>';
	private   static $jg_email = '<a href="mailto:cableman371@gmail.com">%s</a>';

	private static function our_email() { return ( ( mt_rand( 1, 10 ) > 5 ) ? self::$rc_email : self::$jg_email ); }

	public static function activate() {
		$return = false;
		if ( current_user_can( 'activate_plugins' ) ) {
			$return = true;
		}
		return $return;
	}


	/**  Turn things on  **/

	protected static function create_new_page( $new ) {
		$page = get_page_by_title( $new['post_title'] ); // FIXME: should get page by slug instead
		if ( $page ) {
			$class = get_class( $page );
			foreach( $new as $key => $value ) {
				if ( property_exists( $class, $key ) ) {
					$page->$key = $value;
				}
			}
			wp_update_post( $page );
		} else {
			wp_insert_post( $new );
		}
	}
/*
	#	this is not currently being used by anything
	protected static function activate_multisite() {
		#	https://core.trac.wordpress.org/ticket/14170
		global $wpdb;
		if (function_exists('is_multisite') && is_multisite()) {
			if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
				$old_blog = $wpdb->blogid;
				#	Get all blog ids
				$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					self::single_blog_activate();
				}
				switch_to_blog($old_blog);
				return;
			}
		}
		self::single_blog_activate();
	}

	protected static function single_blog_activate() { } //*/

/*
	protected static function theme_dependency() {
		if ( ! function_exists( self::$dep_func ) ) {
			$error_text = self::dependency_string();
			trigger_error( $error_text, E_USER_ERROR );
		}
	} //*/
/*
	public static function check_dependency() {
		if ( current_user_can( 'manage_options' ) ) {
			if ( ! function_exists( self::$dep_func ) ) {
				require_once( ABSPATH . 'wp-admin/include/plugin.php' );
				deactivate_plugins( PMW_BASE ); // FIXME:  plugin name
				$error_text = dependency_string();
				trigger_error( $error_text, E_USER_ERROR );
			}
		}
	} //*/
/*
	private static function dependency_string() {
		$site_name = _x( 'The Creative Collective', 'noun - plugin site name', 'tcc-privacy' );
		$comp_name = _x( 'The Creative Collective', 'noun - plugin company name', 'tcc-privacy');
		$string    = _x( 'This plugin should only be used with %1$s themes by %2$s', 'nouns - 1 is the company, 2 is the website', 'tcc-privacy' );
		$site      = sprintf( self::$our_site, $site_name );
		$company   = sprintf( self::our_email(), $comp_name );
		return sprintf( $string, $site, $company );
	} //*/


	/**  Turn things off  **/

	public static function deactivate( $option = '' ) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$option = self::verify_option( $option );
			if ( $option ) {
				self::delete_blog_options( 'deactive', $option );
				// FIXME: this needs testing, or something
#				self::delete_site_options( 'deactive', $option );
				flush_rewrite_rules();
			}
		}
	}

	public static function uninstall( $option = '' ) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$option = self::verify_option( $option );
			if ( $option ) {
				self::delete_blog_options( 'uninstall', $option );
				//  FIXME  see above note
#				self::delete_site_options( 'uninstall', $option );
			}
		}
	}

	private static function verify_option( $option ) {
		$option = ( $option )
			? $option
			: ( ( ! empty( self::$option ) )
				? self::$option
				: $option );
		return $option;
	}

	protected static function delete_blog_options( $action, $option ) {
		$log_id   = get_current_blog_id();
		$opt_slug = self::$prefix . $option;
		$options  = get_blog_option( $blog_id, $opt_slug );
		if ( $options ) {
			#	Is there an $action option?  What is it?
			if ( isset( $options[ $action] ) && ( $options[ $action ] === 'no' ) ) {
				#	No option or 'no' option, don't do anything
			} else {
				delete_blog_option( $blog_id, $opt_slug );
			}
		}
	}

	protected static function delete_site_options( $action, $option ) {
		$opt_slug = self::$prefix . $option;
		$options  = get_site_option( $blog_id, "tcc_options_$option" );
		if ( $options ) {
			#| Is there an $action option?  What is it?
			if ( isset( $options[ $action] ) && ( $options[ $action ] === 'no' ) ) {
				#| No option or 'no' option, don't do anything
			} else {
				delete_site_option( $blog_id, "tcc_options_$option" );
			}
		}
	}


}  #  End of class PMW_Register_Register
