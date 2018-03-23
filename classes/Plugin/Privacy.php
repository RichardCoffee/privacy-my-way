<?php

class PMW_Plugin_Privacy extends PMW_Plugin_Plugin {


	private   $checker  = null;
	protected $github   = 'https://github.com/RichardCoffee/privacy-my-way/';
	protected $privacy  = null;
	protected $setting  = 'options-general.php?page=privacy';
	protected $slug     = 'privacy-my-way';
	protected $tab      = 'privacy';

	use PMW_Trait_Singleton;


	public function initialize() {
		if ( ( ! PMW_Register_Privacy::php_version_check() ) || ( ! PMW_Register_Privacy::wp_version_check() ) ) {
			return;
		}
		register_deactivation_hook( $this->paths->file, array( 'PMW_Register_Privacy', 'deactivate' ) );
		register_uninstall_hook(    $this->paths->file, array( 'PMW_Register_Privacy', 'uninstall'  ) );
		if ( ! is_multisite() || is_main_site() ) {
			$this->add_actions();
			$this->add_filters();
			if ( WP_DEBUG ) {
#				add_filter( 'pre_set_site_transient_update_themes', array( $this, 'site_transient_stack' ), 10, 2 );
				if ( file_exists( WP_CONTENT_DIR . '/pmw-run-tests.flg' ) ) {
					$this->run_tests();
				}
			}
		}
	}

	public function add_actions() {
		$actions = array(
			'admin_init',
			'load-plugins.php',
			'load-themes.php',
			'load-update.php',
			'load-update-core.php',
			'wp_update_plugins',
			'wp_update_themes',
			'wp_version_check',
		);
		foreach( $actions as $action ) {
			add_action( $action, array( $this, 'add_privacy_filters' ), 1 );
		}
		if ( is_admin() ) {
			new PMW_Form_Privacy;
		}
		parent::add_actions();
	}

	public function add_filters() {
		add_filter( 'core_version_check_locale',     array( $this, 'add_privacy_filters' ) );
		add_filter( 'fluidity_initialize_options',   array( $this, 'add_privacy_options' ) );
		$options = get_option( 'tcc_options_privacy', array() );
		if ( isset( $options['autoupdate'] ) ) {
			if ( $options['autoupdate'] === 'no' ) {
				add_filter( 'automatic_updater_disabled', '__return_true' );
			} else if ( $options['autoupdate'] === 'core' ) {
				add_filter( 'auto_update_plugin', '__return_false', 10, 2 );
				add_filter( 'auto_update_theme', '__return_false', 10, 2 );
				add_filter( 'auto_update_translation', '__return_false', 10, 2 );
			}
		}
		parent::add_filters();
	}

	public function add_privacy_filters( $locale = '' ) {
		$this->privacy_setup();
		return $locale;
	}

	public function add_privacy_options( $options ) {
		$this->setting = 'admin.php?page=fluidity_options&tab=privacy';
		$options['Privacy'] = new PMW_Options_Privacy;
		return $options;
	}

	private function privacy_setup() {
		if ( ! $this->privacy ) {
			include_once( $this->paths->dir . 'classes/privacy.php' );
			$this->privacy = Privacy_My_Way::instance();
		}
	}


	/**  Tests  **/

	private function run_tests() {
		$this->privacy_setup();
		$plugins = get_plugins();
		$active  = get_option( 'active_plugins', array() );
		$args    = array(
			'plugins' => array(
				'function' => 'filter_plugins',
				'url'      => 'https://api.wordpress.org/plugins/update-check/',
				'args'     =>  array(
					'body' => array(
						'plugins' => wp_json_encode( compact( 'plugins', 'active' ) ),
					),
				),
			),
			'themes' => array(
				'function' => 'filter_themes',
				'url'      => 'https://api.wordpress.org/themes/update-check/',
				'args'     =>  array(
					'body' => array(
						'themes' => wp_json_encode( $this->get_installed_themes() ),
					),
				),
			),
		);
		$this->privacy->run_tests( $args );
	}

	private function get_installed_themes() {
		$installed = wp_get_themes();
		$themes    = array();
		$active    = get_option( 'stylesheet' );
		foreach ( $installed as $theme ) {
			$style = $theme->get_stylesheet();
			$themes[ $style ] = array(
#				'Name'       => $theme->get('Name'),
#				'Title'      => $theme->get('Name'),
#				'Version'    => $theme->get('Version'),
#				'Author'     => $theme->get('Author'),
#				'Author URI' => $theme->get('AuthorURI'),
				'Template'   => $theme->get_template(),
				'Stylesheet' => $style,
			);
		}
		return compact( 'active', 'themes' );
	}

	public function site_transient_stack( $data, $transient ) {
		pmw_log_entry( $transient, $data, 'stack' );
		return $data;
	}


}
