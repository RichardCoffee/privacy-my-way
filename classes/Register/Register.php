<?php
/**
 *  Provides functions for registering a plugin
 *
 * @package Privacy_My_Way
 * @subpackage Register
 * @since 20170111
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Register/Register.php
 */
defined( 'ABSPATH' ) || exit;


class PMW_Register_Register {


	/**
	 * @since 20170227
	 * @var string
	 */
	protected static $option = 'plugin_option_slug';
	/**
	 * @since 20170318
	 * @var string  class trait added
	 */
	protected static $php_vers = '5.3.6';
	/**
	 * @since 20170227
	 * @var string  Option slug prefix.
	 */
	protected static $prefix = 'option_prefix_';
	/**
	 * @since 20200406
	 * @var string  Name of class.
	 */
	protected static $register = 'PMW_Register_Register';
	/**
	 * @since 20170318
	 * @var string  Plugin title.
	 */
	protected static $title = 'Plugin Name';
	/**
	 * @since 20170318
	 * @var string  get_theme_file_uri() added
	 */
	protected static $wp_vers  = '4.7.0';

	/**
	 *  Provide an email address.
	 *
	 * @since 20170214
	 */
	private static $our_site = '<a href="rtcenterprises.net" target="rtc">%s</a>';
	private static $rc_email = '<a href="mailto:richard.coffee@rtcenterprises.net">%s</a>';
//	private static $jg_email = '<a href="mailto:cableman371@gmail.com">%s</a>';
//	private static function our_email() { return ( ( mt_rand( 1, 10 ) > 5 ) ? self::$rc_email : self::$jg_email ); }
	public static function our_email() { return self::$rc_email; }

	/**
	 *  Preforms preliminary version checks.
	 *
	 * @since 20170214
	 */
	public static function activate() {
		if ( ! static::php_version_check() ) {
			wp_die( esc_html( static::php_bad_version_text() ) . self::return_link() );
		}
		if ( ! static::wp_version_check() ) {
			wp_die( esc_html( static::wp_bad_version_text() ) . self::return_link() );
		}
		static::activate_tasks();
	}

	/**
	 *  Should be overridden in child class.  Performs all tasks required when activating the plugin.
	 *
	 * @since 20170501
	 */
	protected static function activate_tasks() { }


	/**  Dependency Checking - Will It Work?
	 *
	 * @link https://github.com/GlotPress/GlotPress-WP/blob/develop/glotpress.php
	 * @link https://pento.net/2014/02/18/dont-let-your-plugin-be-activated-on-incompatible-sites/
	 * @link https://buddypress.trac.wordpress.org/attachment/ticket/7196/7196.diff
	 */

	/**  PHP version check  **/

	/**
	 *  Returns the PHP version required.
	 *
	 * @since 20170318
	 * @return string  PHP version string.
	 */
	protected static function php_version_required() {
		return static::$php_vers;
	}

	/**
	 *  Do the PHP version check.
	 *
	 * @since 20170318
	 * @return bool  Result of the version check.
	 */
	public static function php_version_check() {
		if ( version_compare( phpversion(), static::php_version_required(), '<' ) ) {
			add_action( 'admin_notices', [ static::$register, 'unsupported_php_version' ], 10, 2 );
			return false;
		}
		return true;
	}

	/**
	 *  Handles bad PHP versions.
	 *
	 * @since 20170318
	 */
	public static function unsupported_php_version() {
		$short = __( 'You are running an unsupported version of PHP.', 'privacy-my-way' );
		$long  = static::php_bad_version_text();
		self::display_admin_notice( '&#151; ' . $short, $long );
	}

	/**
	 *  Returns the string for bad PHP version.
	 *
	 * @since 20170318
	 * @return string  Text for bad PHP version.
	 */
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

	/**
	 *  Return the WP version required.
	 *
	 * @since 20170318
	 * @return string  Required WP version.
	 */
	protected static function wp_version_required() {
		return static::$wp_vers;
	}

	/**
	 *  Handles the WP version check.
	 *
	 * @since 20170318
	 * @return bool  Success/failure of version check.
	 */
	public static function wp_version_check() {
		global $wp_version;
		if ( version_compare( $wp_version, static::wp_version_required(), '<' ) ) {
			add_action( 'admin_notices', [ static::$register, 'unsupported_wp_version' ], 10, 2 );
			return false;
		}
		return true;
	}

	/**
	 *  Handles bad wp version.
	 *
	 * @since 20170318
	 */
	public static function unsupported_wp_version() {
		$short = __( 'You are running an unsupported version of WordPress.', 'privacy-my-way' );
		$long  = static::wp_bad_version_text();
		self::display_admin_notice( '&#151; ' . $short, $long );
	}

	/**
	 *  Returns text for bad WP version.
	 *
	 * @since 20170318
	 * @return string  Text.
	 */
	protected static function wp_bad_version_text() {
		global $wp_version;
		return sprintf(
			_x(
				'%1$s requires WordPress %2$s or later and has detected you are running %3$s. Upgrade your WordPress install before using this plugin.',
				'1: Plugin name  2: Required version of WordPress  3: Current version of WordPress',
				'privacy-my-way'
			),
			static::$title,
			static::wp_version_required(),
			$wp_version
		);
	}

	/**
	 *  Handles displaying the admin notice.
	 *
	 * @since 20170318
	 * @param string  Notice title message.
	 * @param string  Notice body message.
	 */
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

	/**
	 *  Gives return anchor link for plugin install page.
	 *
	 * @since 20170501
	 * @return string  HTML for anchor link.
	 */
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

	/**
	 *  Creates a new page, or modifies a current page.
	 *
	 * @since 20170214
	 * @param array  Post array.
	 * @param bool   If true, will overwrite existing page.
	 */
	protected static function create_new_page( $new = array(), $overwrite = false ) {
		if ( $new ) {
			$page = false;
			if ( ! empty( $new['post_name'] ) ) {
				global $wpdb;
				$page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM posts WHERE post_name = %s AND post_type = 'page'", $new['post_name'] ) );
				if ( $page_id ) {
					$page = get_post( $page_id );
				}
			}
			if ( ( ! $page ) && array_key_exists( 'post_title', $new ) && $new['post_title'] ) {
				$page = get_page_by_title( $new['post_title'] );
			}
			if ( $page ) {
				$new = array_merge( (array) $page, $new );
			}
			if ( $overwrite && array_key_exists( 'ID', $new ) && $new['ID'] ) {
				wp_update_post( $new );
			} else {
				unset( $new['ID'] );
				wp_insert_post( $new );
			}
		}
	}


	/**  Turn things off  **/

	/**
	 *  Removes plugin options from database on plugin deactivation.
	 *
	 * @since 20170111
	 * @param string  Option slug.
	 */
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

	/**
	 *  Removes plugin options from the wp database when uninstalling plugin.
	 *
	 * @since 20170111
	 * @param string  Option slug.
	 */
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

	/**
	 *  Provides for default value of option slug.  Does not actually 'verify' anything.
	 *
	 * @since 20170227
	 * @param string   Option slug.
	 * @return string  Options slug.
	 */
	private static function verify_option( $option ) {
		$option = ( $option )
			? $option
			: ( static::$option )
				? static::$option
				: null;
		return $option;
	}

	/**
	 *  Delete options for a multi-site blog.
	 *
	 * @since 20170227
	 * @param string  Action to take.
	 * @param string  Option slug.
	 */
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

	/**
	 *  Delete single site option.
	 *
	 * @since 20170227
	 * @param string  Actino to take.
	 * @param string  Option slug.
	 */
	protected static function delete_site_options( $action, $option ) {
		$opt_slug = static::$prefix . $option;
		$options  = get_site_option( $opt_slug, array() );
		if ( $options ) {
			if ( array_key_exists( 'deledata', $options ) && ( $options['deledata'] === $action ) ) {
				delete_site_option( $opt_slug );
			}
		}
	}


}
