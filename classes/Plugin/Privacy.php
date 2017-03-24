<?php


class PMW_Plugin_Privacy extends PMW_Plugin_Plugin {

	private   $checker  = null;
	private   $debug    = true;
	protected $privacy  = null;
	protected $puc_vers = '4.0.3';
	protected $setting  = 'options-general.php?page=privacy';
	protected $tab      = 'privacy';

	use PMW_Trait_Singleton;

	public function initialize() {
		if ( ( ! PMW_Register_Privacy::php_version_check() ) || ( ! PMW_Register_Privacy::wp_version_check() ) ) {
			return;
		}
		register_deactivation_hook( $this->paths->file, array( 'PMW_Register_Privacy', 'deactivate' ) );
		register_uninstall_hook(    $this->paths->file, array( 'PMW_Register_Privacy', 'uninstall'  ) );
		$this->load_update_checker();
		$args = array(
			'text_domain' => 'Text Domain',
			'lang_dir'    => 'Domain Path',
		);
		$data = get_file_data( $this->paths->file, $args );
		load_plugin_textdomain( $data['text_domain'], false, $this->paths->dir . $data['lang_dir'] );
		$this->add_actions();
		$this->add_filters();
		if ( WP_DEBUG && $this->debug ) {
			$this->run_tests();
		}
	}

	public function add_actions() {
		add_action( 'wp_version_check', array( $this, 'add_privacy_filters' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( PMW_Form_Privacy::instance(), 'add_menu_option' ) );
		}
		parent::add_actions();
	}

	public function add_filters() {
		add_filter( 'core_version_check_locale',   array( $this, 'add_privacy_filters' ) );
		add_filter( 'fluidity_initialize_options', array( $this, 'add_privacy_options' ) );
		parent::add_filters();
	}

	private function privacy_setup() {
		if ( ! $this->privacy ) {
			if ( ! function_exists( 'random_int' ) ) {
				# PHP 7.0 compatibility
				require_once( $this->paths->dir . 'assets/random_compat/lib/random.php' );
			}
			include_once( $this->paths->dir . 'classes/privacy.php' );
			$this->privacy = Privacy_My_Way::instance();
		}
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

	private function load_update_checker() {
		require_once( $this->paths->dir . 'assets/plugin-update-checker-' . $this->puc_vers . '/plugin-update-checker.php' );
		$this->checker = Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/RichardCoffee/privacy-my-way/',
			$this->paths->file,
			'privacy-my-way'
		);
#log_entry($this->checker);
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
			$themes[ $theme->get_stylesheet() ] = array(
#				'Name'       => $theme->get('Name'),
#				'Title'      => $theme->get('Name'),
#				'Version'    => $theme->get('Version'),
#				'Author'     => $theme->get('Author'),
#				'Author URI' => $theme->get('AuthorURI'),
				'Template'   => $theme->get_template(),
				'Stylesheet' => $theme->get_stylesheet(),
			);
		}
		return compact( 'active', 'themes' );
	}


}
