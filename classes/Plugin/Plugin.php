<?php
/**
 *   Abstract class that contains helper functions for a plugin.
 *
 * @package Privacy_My_Way
 * @subpackage Plugin_Core
 * @since 20170111
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Plugin/Plugin.php
 */
defined( 'ABSPATH' ) || exit;

abstract class PMW_Plugin_Plugin {


#	 * @since 20170111
	protected $admin = null;
#	 * @since 20170111
	public    $dbvers = '0';
	/**
	 * @since 20200221
	 * @var string  Branch to be used in conjunction with https://github.com/YahnisElsts/plugin-update-checker
	 */
	protected $branch = 'master';
	/**
	 * @since 20200329
	 * @var string  Text domain string, DO NOT USE as a variable.
	 */
	protected $domain = 'plugin-domain';
	/**
	 * @since 20170325
	 * @var string  Github address used in conjunction with https://github.com/YahnisElsts/plugin-update-checker.
	 */
	protected $github = '';
	/**
	 * @since 20200329
	 * @var string  Language dir path.
	 */
	protected $lang = '/languages';
	/**
	 *
	 * @since 20170113
	 * @var object  PMW_Plugin_Paths object.
	 */
	public $paths = null;
	/**
	 *
	 * @since 20170111
	 * @var string  Slug for the plugin.
	 */
	public $plugin = 'plugin-slug';
	/**
	 *
	 * @since 20200205
	 * @var int  The priority used when loading via the 'plugins_loaded' hook
	 */
	protected $priority = 10;
	/**
	 *
	 * @since 20170207
	 * @var string  Wordpress link to a settings page for the plugin.  Shown on the admin plugins list page.
	 */
	protected $setting = '';
	/**
	 *
	 * @since 20170207
	 * @var string  Used for integration purposes with certain themes and plugins.  Can be safely ignored.
	 */
	protected $state = 'alone';
	/**
	 *
	 * @since 20170111
	 * @var string  Used for a settings tab in conjunction with certain themes.  Can be safely ignored.
	 */
	protected $tab = 'about';


	/**
	 * @since 20170214
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Magic.php
	 */
	use PMW_Trait_Magic;
	/**
	 * @since 20170214
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/ParseArgs.php
	 */
	use PMW_Trait_ParseArgs;


	/**
	 *  Method that should be used to setup the required plugin environment.  Will be called in 'plugins_loaded' hook with a priority of $this->priority.
	 *
	 * @since 20170214
	 */
	abstract public function initialize();


	/**
	 *  Provides basic initialization for the plugin.
	 *
	 * @since 20170111
	 * @param array Contains values to be loaded into plugin properties.
	 */
	protected function __construct( $args = array() ) {
		if ( ! empty( $args['file'] ) ) {
			$seek = array(
				'ver'    => 'Version',
				'github' => 'Github URI',
				'domain' => 'Text Domain',
				'lang'   => 'Domain Path',
			);
			$data = get_file_data( $args['file'], $seek );
			$defaults = array(
				'dir'     => plugin_dir_path( $args['file'] ),
				'plugin'  => dirname( plugin_basename( $args['file'] ) ),
				'url'     => plugin_dir_url( $args['file'] ),
				'version' => $data['ver'],
			);
			if ( is_url( $data['github'] ) )           $defaults['github'] = $data['github'];
			if ( in_array( $this->tab, [ 'about' ] ) ) $this->tab = $defaults['plugin'];
			$args = array_merge( $defaults, $args );
			$this->parse_args( $args );
			$this->paths = PMW_Plugin_Paths::get_instance( $args );
			$this->state = $this->state_check();
			$this->schedule_initialize();
			$this->load_textdomain();
			$this->load_update_checker();
		} else {
			static::$abort__construct = true;
		}
	}

	/**
	 *  Default plugin add_action calls.
	 *
	 * @since 20170111
	 */
	public function add_actions() { }

	/**
	 *  Default plugin add_filter calls.
	 *
	 * @since 20170111
	 */
	public function add_filters() {
		add_filter( 'plugin_action_links', [ $this, 'settings_link' ], 10, 4 );
		add_filter( 'network_admin_plugin_action_links', [ $this, 'settings_link' ], 10, 4 );
	}


	/**  General functions  **/

	/**
	 *  Detects presence of themes and other plugins this plugin may be compatible with.
	 *
	 * @since 20170207
	 */
	public function state_check() {
		$state = 'alone';
		if ( is_readable( get_template_directory() . '/classes/Form/Admin.php' ) ) {
			$state = 'theme';
		} else {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'tcc-theme-options/tcc-theme-options.php' ) ) {
				$state = 'plugin';
			}
		}
		return $state;
	}

	/**
	 *  Decides when to initialize plugin.
	 *
	 * @since 20170227
	 */
	protected function schedule_initialize() {
		switch ( $this->state ) {
			case 'plugin':
				add_action( 'tcc_theme_options_loaded', [ $this, 'initialize' ] );
				break;
			case 'alone':
			case 'theme':
			default:
				add_action( 'plugins_loaded', [ $this, 'initialize' ], $this->priority );
		}
	}

	/**
	 *  Loads the plugin's language file, if present.
	 *
	 * @since 20170325
	 * @link https://github.com/schemapress/Schema
	 */
	private function load_textdomain() {
		if ( ! empty( $this->domain ) ) {
			list( $lang_dir, $mofile_local, $mofile_global ) = $this->determine_textdomain_filenames();
			if ( is_readable( $mofile_global ) ) {
				load_textdomain( $this->domain, $mofile_global );
			} else if ( is_readable( $mofile_local ) ) {
				load_textdomain( $this->domain, $mofile_local );
			} else {
				load_plugin_textdomain( $this->domain, false, $lang_dir );
			}
		}
	}

	/**
	 *  Tries to determine where the language files should be at.
	 *
	 * @since 20170409
	 * @param array Should contain data read from the main plugin file.
	 * @return array Possible locations of language files
	 */
	private function determine_textdomain_filenames() {
		//  Where the language files should be.
		$lang_def = ( empty( $this->lang ) ) ? '/languages' : $this->lang;
		$files[]  = untrailingslashit( $this->paths->dir ) . $lang_def;
		//  Determine the language file to load.
		$locale   = apply_filters( 'plugin_locale',  get_locale(), $this->domain );
		$mofile   = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );
		$files[]  = $files[0] . '/' . $mofile;
		//  Where a global file might be at.
		$files[]  = WP_LANG_DIR . '/plugins/' . $this->domain . '/' . $mofile;
		return $files;
	}

	/**
	 *  Provides a simple method for retrieving the plugin css file.
	 *
	 * @since 20170111
	 * @param string File to look for.  Alter default as needed.
	 * @return string Server file path.
	 */
	public function get_stylesheet( $file = 'css/privacy-my-way.css' ) {
		return $this->paths->get_plugin_file_path( $file );
	}

	/*
	 *  Removes 'Edit' option from plugin action links, and adds 'Settings' option.
	 *
	 * @since 20170111
	 * @link http://code.tutsplus.com/tutorials/integrating-with-wordpress-ui-the-basics--wp-26713
	 * @link https://hugh.blog/2012/07/27/wordpress-add-plugin-settings-link-to-plugins-page/
	 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links/
	 * @param array  An array of plugin action links.
	 * @param string Path to the plugin file relative to the plugins directory.
	 * @param array  An array of plugin data.
	 * @param string The plugin context.
	 * @return array
	 */
	public function settings_link( $links, $file, $data, $context ) {
		if ( ! in_array( strpos( $file, $this->plugin ), [ false ] ) ) {
			if ( array_key_exists( 'edit', $links ) ) unset( $links['edit'] );
			if ( is_plugin_active( $file ) ) {
				$url = ( $this->setting ) ? $this->setting : admin_url( 'admin.php?page=fluidity_options&tab=' . $this->tab );
				$links['settings'] = sprintf( '<a href="%s"> %s </a>', esc_url( $url ), esc_html__( 'Settings', 'privacy-my-way' ) );
			}
		}
		return $links;
	}


  /** Update functions **/

	/**
	 *  Load the plugin update checker.
	 *
	 * @since 20170325
	 * @link https://github.com/YahnisElsts/plugin-update-checker
	 */
	private function load_update_checker() {
		if ( $this->github ) {
			$puc_file = $this->paths->dir . $this->paths->vendor . 'plugin-update-checker/plugin-update-checker.php';
			if ( is_readable( $puc_file ) ) {
				require_once( $puc_file );
				$puc = Puc_v4_Factory::buildUpdateChecker( $this->github, $this->paths->file, $this->plugin );
				$puc->setBranch( $this->branch );
			}
		}
	}

/*
#	 * @since 20170111
  public function check_update() {
    $addr = 'tcc_options_'.$this->tab;
    $data = get_option($addr);
    if ( ! array_key_exists( 'dbvers', $data ) ) return;
    if (intval($data['dbvers'],10)>=intval($this->dbvers)) return;
    $this->perform_update($addr);
  }

#	 * @since 20170111
  private function perform_update($addr) {
    $option = get_option($addr);
    $dbvers = intval($option['dbvers'],10);
    $target = intval($this->dbvers,10);
    while($dbvers<$target) {
      $dbvers++;
      $update_func = "update_$dbvers";
      if ( method_exists( get_called_class(), $update_func ) ) {
        $this->$update_func();
      }
    }
    $option = get_option($addr); // reload in case an update changes an array value
    $option['dbvers']  = $dbvers;
    $option['version'] = $this->paths->version;
    update_option($addr,$option);
  } //*/


}

/**
 *  Test for a valid url.
 *
 * @since 20200227
 * @param string $url  URL to check.
 * @return bool        True for a valid url, false otherwise.
 */
if ( ! function_exists( 'is_url' ) ) {
	function is_url( $url ) {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}
}
