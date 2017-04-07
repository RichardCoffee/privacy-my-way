<?php

abstract class PMW_Plugin_Plugin {

	protected $admin    = null;
	public    $dbvers   = '0';
	protected $github   = '';    #  'https://github.com/MyGithubName/my-plugin-name/';
	public    $paths    = null;  #  PMW_Plugin_Paths object
	public    $plugin   = '';
	protected $puc      = null;
	private   $puc_vers = '4.1';
	protected $setting  = '';    #  settings link
	protected $state    = '';
	protected $tab      = 'about';
	public    $version  = '0.0.0';

	use PMW_Trait_Magic;
	use PMW_Trait_ParseArgs;

	abstract public function initialize();

	protected function __construct( $args = array() ) {
		if ( isset( $args['file'] ) ) {
			$data = get_file_data( $args['file'], array( 'ver' => 'Version' ) );
			$defaults = array(
				'dir'     => plugin_dir_path( $args['file'] ),
				'plugin'  => dirname( plugin_basename( $args['file'] ) ),
				'url'     => plugin_dir_url( $args['file'] ),
				'version' => $data['ver'],
			);
			$args = array_merge( $defaults, $args );
			$this->parse_args( $args );
			$this->paths = PMW_Plugin_Paths::get_instance( $args );
			$this->state = $this->state_check();
			$this->schedule_initialize();
			$this->load_text_domain();
			$this->load_update_checker();
		} else {
			static::$abort_construct = true;
		}
	}

	public function add_actions() { }

	public function add_filters() {
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
	} //*/


	/**  General functions  **/

	public function state_check() {
		$state = 'alone';
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'tcc-theme-options/tcc-theme-options.php' ) ) {
			$state = 'plugin';
		}
		if ( file_exists( get_template_directory() . '/classes/Form/Admin.php' ) ) {
			$state = 'theme';
		}
		return $state;
	}

	protected function schedule_initialize() {
		switch ( $this->state ) {
			case 'plugin': # Deprecated, theme options is no longer a plugin
				add_action( 'tcc_theme_options_loaded', array( $this, 'initialize' ) );
				break;
			case 'alone':
			case 'theme':
			default:
				add_action( 'plugins_loaded', array( $this, 'initialize' ), 100 );
		}
	}

	private function load_text_domain() {
		$args = array(
			'text_domain' => 'Text Domain',
			'lang_dir'    => 'Domain Path',
		);
		$data = get_file_data( $this->paths->file, $args );
		load_plugin_textdomain( $data['text_domain'], false, $this->paths->dir . $data['lang_dir'] );
	}

	/**  Template functions **/

	public function get_stylesheet( $file = 'css/tcc-privacy.css' ) {
		return $this->paths->get_plugin_file_path( $file );
	}

	/*
	 *  Removes 'Edit' option from plugin page entry
	 *  Adds 'Settings' option to plugin page entry
	 *
	 *  sources:  http://code.tutsplus.com/tutorials/integrating-with-wordpress-ui-the-basics--wp-26713
	 */
	public function settings_link( $links, $file ) {
		if ( strpos( $file, $this->plugin ) > -1 ) {
			unset( $links['edit'] );
			if ( is_plugin_active( $file ) ) { // NOTE:  how would this ever get run if the plugin is not active?  why do we need this check?
				$url   = ( $this->setting ) ? $this->setting : admin_url( 'admin.php?page=fluidity_options&tab=' . $this->tab );
				$links['settings'] = sprintf( '<a href="%1$s"> %2$s </a>', $url, esc_html__( 'Settings', 'tcc-privacy' ) );
			}
		}
		return $links;
	}

	/**  Updates  **/

	private function load_update_checker() {
		$puc_file = $this->paths->dir . 'vendors/plugin-update-checker-' . $this->puc_vers . '/plugin-update-checker.php';
		if ( file_exists( $puc_file ) && ! empty( $this->github ) ) {
			require_once( $puc_file );
			$this->puc = Puc_v4_Factory::buildUpdateChecker( $this->github, $this->paths->file, $this->plugin );
		}
	}


}
