<?php

defined( 'ABSPATH' ) || exit;

class PMW_Register_Register {

	protected static $options  = 'about';
	protected static $php_vers = '5.3.6';         #  trait feature added
	protected static $prefix   = 'tcc_options_';  #  Option slug prefix
	protected static $title    = 'This plugin';
	protected static $wp_vers  = '4.7.0';         #  added get_theme_file_uri function

	private   static $our_site = '<a href="the-creative-collective.com" target="tcc">%s</a>';
	private   static $rc_email = '<a href="mailto:richard.coffee@rtcenterprises.net">%s</a>';
	private   static $jg_email = '<a href="mailto:cableman371@gmail.com">%s</a>';

	private static function our_email() { return ( ( mt_rand( 1, 10 ) > 5 ) ? self::$rc_email : self::$jg_email ); }

	public static function activate() {
		if ( ! static::php_version_check() ) {
			wp_die( static::php_bad_version_text() );
		}
		if ( ! static::wp_version_check() ) {
			wp_die( static::wp_bad_version_text() );
		}
	}


	/**  Dependency Checking - Will It Work?  **/

	#	https://github.com/GlotPress/GlotPress-WP/blob/develop/glotpress.php
	#	https://pento.net/2014/02/18/dont-let-your-plugin-be-activated-on-incompatible-sites/
	#	https://buddypress.trac.wordpress.org/attachment/ticket/7196/7196.diff

	/**  PHP version check  **/

	protected static function php_version_required() {
		return static::$php_vers;
	}

	public static function php_version_check() {
		if ( version_compare( phpversion(), static::php_version_required(), '<' ) ) {
			add_action( 'admin_notices', array( 'PMW_Register_Register', 'unsupported_php_version' ), 10, 2 );
			return false;
		}
		return true;
	}

	public static function unsupported_php_version() {
		$short = __( '&#151; You are running an unsupported version of PHP.', 'tcc-privacy' );
		$long  = static::php_bad_version_text();
		self::display_admin_notice( $short, $long );
	}

	protected static function php_bad_version_text() {
		return sprintf(
			_x(
				'%1$s requires PHP version %2$s, version %3$s detected.  Please upgrade your PHP before attempting to use this plugin. ',
				'1: Plugin name   2: php version required  3: php version detected',
				'tcc-privacy'
			),
			static::$title,
			static::$php_vers,
			phpversion()
		);
	}


	/**  WordPress version check  **/

	protected static function wp_version_required() {
		return static::$wp_vers;
	}

	public static function wp_version_check() {
		if ( version_compare( $GLOBALS['wp_version'], static::wp_version_required(), '<' ) ) {
			add_action( 'admin_notices', array( 'PMW_Register_Register', 'unsupported_wp_version' ), 10, 2 );
			return false;
		}
		return true;
	}

	public static function unsupported_wp_version() {
		$short = __( '&#151; You are running an unsupported version of WordPress.', 'tcc-privacy' );
		$long  = static::wp_bad_version_text();
		self::display_admin_notice( $short, $long );
	}

	protected static function wp_bad_version_text() {
		return sprintf(
			_x(
				'%1$s requires WordPress %2$s or later and has detected you are running %3$s. Upgrade your WordPress install before using this plugin.',
				'1: Plugin name  2: Required version of WordPress  3: Current version of WordPress',
				'tcc-privacy'
			),
			static::$title,
			static::wp_version_required(),
			$GLOBALS['wp_version']
		);
	}

	private static function display_admin_notice( $short, $long ) {
		$screen = get_current_screen();
		if ( 'plugins' !== $screen->id ) {
			return;
		} ?>
		<div class="notice notice-error">
			<p style="max-width:800px;">
				<b><?php echo esc_html( sprintf( _x( '%s can not be activated.', 'Plugin title', 'tcc-privacy' ), self::$title ) );?></b>
				<?php echo esc_html( $short ); ?>
			</p>
			<p style="max-width:800px;">
				<?php echo esc_html( $long ); ?>
			</p>
		</div><?php
	}


	/**  Turn things on  **/

	protected static function create_new_page( $new = array() ) {
		if ( $new ) {
			$page = false;
			if ( ! empty( $new['post_name'] ) ) {
				global $wpdb;
				$page_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '$slug' AND post_type = 'page'" );
				if ( $page_id ) {
					$page = get_post( $page_id );
				}
			}
			if ( ! $page && ! empty( $new['post_title'] ) ) {
				$page = get_page_by_title( $new['post_title'] );
			}
			if ( $page ) {
				$new = array_merge( (array) $page, $new );
				unset( $new['ID'] );
			}
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
