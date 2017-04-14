<?php


class PMW_Plugin_Privacy extends PMW_Plugin_Plugin {

	private   $checker  = null;
	protected $github   = 'https://github.com/RichardCoffee/privacy-my-way/';
	protected $privacy  = null;
	protected $random   = '2.0.9';
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

		$this->add_actions();
		$this->add_filters();

		if ( WP_DEBUG && file_exists( WP_CONTENT_DIR . '/run-tests.flg' ) ) {
			$this->run_tests();
		}
	}

	public function add_actions() {
		add_action( 'wp_version_check', array( $this, 'add_privacy_filters' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( PMW_Form_Privacy::instance(), 'add_menu_option' ) );
			add_action( 'tcc_load_form_page', function() {
				add_action( 'admin_enqueue_scripts', array( PMW_Form_Privacy::instance(), 'enqueue_theme_scripts' ) );
			});
		}
		parent::add_actions();
	}

	public function add_filters() {
		add_filter( 'core_version_check_locale',   array( $this, 'add_privacy_filters' ) );
		add_filter( 'fluidity_initialize_options', array( $this, 'add_privacy_options' ) );
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
		if ( ! $this->privacy ) { /*
			if ( ! function_exists( 'random_int' ) ) {
				# PHP 7.0 compatibility
				require_once( $this->paths->dir . 'vendor/random_compat-' . $this->random . '/lib/random.php' );
			} //*/
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


}
