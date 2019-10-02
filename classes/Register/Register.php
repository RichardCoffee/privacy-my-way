<?php

defined( 'ABSPATH' ) || exit;

class PMW_Register_Register {

	protected static $options  = 'about';
	protected static $php_vers = '5.3.6';         #  trait feature added
	protected static $prefix   = 'tcc_options_';  #  Option slug prefix
	protected static $title    = 'This plugin';
	protected static $wp_vers  = '4.7.0';         #  get_theme_file_uri function added

	private static $our_site = '<a href="rtcenterprises.net" target="rtc">%s</a>';
	private static $rc_email = '<a href="mailto:richard.coffee@rtcenterprises.net">%s</a>';
	private static $jg_email = '<a href="mailto:cableman371@gmail.com">%s</a>';
	private static function our_email() { return ( ( mt_rand( 1, 10 ) > 5 ) ? self::$rc_email : self::$jg_email ); }


	public static function activate() {
		if ( ! static::php_version_check() ) {
			wp_die( esc_html( static::php_bad_version_text() ) . self::return_link() );
		}
		if ( ! static::wp_version_check() ) {
			wp_die( esc_html( static::wp_bad_version_text() ) . self::return_link() );
		}
		static::activate_tasks();
	}

	protected static function activate_tasks() { }


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
			add_action( 'admin_notices', [ 'PMW_Register_Register', 'unsupported_php_version' ], 10, 2 );
			return false;
		}
		return true;
	}

	public static function unsupported_php_version() {
		$short = __( 'You are running an unsupported version of PHP.', 'privacy-my-way' );
		$long  = static::php_bad_version_text();
		self::display_admin_notice( '&#151; ' . $short, $long );
	}

	protected static function php_bad_version_text() {
		return sprintf(
			_x(
				'%1$s requires PHP version %2$s, version %3$s detected.  Please upgrade your PHP before attempting to use this plugin. ',
				'1: Plugin name   2: php version required  3: php version detected',
				'privacy-my-way'
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
			add_action( 'admin_notices', [ 'PMW_Register_Register', 'unsupported_wp_version' ], 10, 2 );
			return false;
		}
		return true;
	}

	public static function unsupported_wp_version() {
		$short = __( 'You are running an unsupported version of WordPress.', 'privacy-my-way' );
		$long  = static::wp_bad_version_text();
		self::display_admin_notice( '&#151; ' . $short, $long );
	}

	protected static function wp_bad_version_text() {
		return sprintf(
			_x(
				'%1$s requires WordPress %2$s or later and has detected you are running %3$s. Upgrade your WordPress install before using this plugin.',
				'1: Plugin name  2: Required version of WordPress  3: Current version of WordPress',
				'privacy-my-way'
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
				<b><?php echo esc_html( sprintf( _x( '%s can not be activated.', 'Plugin title', 'privacy-my-way' ), static::$title ) );?></b>
				<?php echo esc_html( $short ); ?>
			</p>
			<p style="max-width:800px;">
				<?php echo esc_html( $long ); ?>
			</p>
		</div><?php
	}

	private static function return_link() {
		ob_start(); ?>
			<p>
				<a href="<?php echo esc_url( get_site_url() . '/wp-admin/plugins.php' ); ?>">
					<?php esc_html_e( 'Return to plugin page.' ); ?>
				</a>
			</p><?php
		return ob_get_clean();
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


	/**  Turn things off  **/

	public static function deactivate( $option = '' ) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$option = self::verify_option( $option );
			if ( $option ) {
				self::delete_blog_options( 'deactive', $option );
				if ( is_multisite() && is_main_site() ) {
					self::delete_site_options( 'deactive', $option );
				}
				flush_rewrite_rules();
			}
		}
	}

	public static function uninstall( $option = '' ) {
		if ( current_user_can( 'activate_plugins' ) ) {
			$option = self::verify_option( $option );
			if ( $option ) {
				self::delete_blog_options( 'uninstall', $option );
				if ( is_multisite() && is_main_site() ) {
					self::delete_site_options( 'uninstall', $option );
				}
			}
		}
	}

	private static function verify_option( $option ) {
		$option = ( $option )
			? $option
			: ( ( ! empty( static::$option ) )
				? static::$option
				: null );
		return $option;
	}

	protected static function delete_blog_options( $action, $option ) {
		$blog_id  = get_current_blog_id();
		$opt_slug = static::$prefix . $option;
		$options  = ( is_multisite() ) ? get_blog_option( $blog_id, $opt_slug, array() ) : get_option( $opt_slug, array() );
		if ( $options ) {
			if ( array_key_exists( 'deledata', $options ) && ( $options['deledata'] === $action ) ) {
				if ( is_multisite() ) {
					delete_blog_option( $blog_id, $opt_slug );
				} else {
					delete_option( $opt_slug );
				}
			}
		}
	}

	protected static function delete_site_options( $action, $option ) {
		$opt_slug = static::$prefix . $option;
		$options  = get_site_option( $opt_slug, array() );
		if ( $options ) {
			if ( array_key_exists( 'deledata', $options ) && ( $options['deledata'] === $action ) ) {
				delete_site_option( $opt_slug );
			}
		}
	}


}  #  End of class PMW_Register_Register
