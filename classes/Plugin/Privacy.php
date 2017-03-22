<?php


class PMW_Plugin_Privacy extends PMW_Plugin_Plugin {

	private   $checker  = null;
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
		load_plugin_textdomain( $data['text_domain'], false, $this->paths->plugin . $data['lang_dir'] );

		$this->add_actions();
		$this->add_filters();

		if ( WP_DEBUG ) {
#			$this->run_tests();
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
		if ( ! function_exists( 'random_int' ) ) {
			# PHP 7.0 compatibility
			require_once( $this->paths->dir . 'assets/random_compat/lib/random.php' );
		}
		include_once( $this->paths->dir . 'classes/privacy.php' );
	}

	public function add_privacy_filters( $locale = '' ) {
		$this->privacy_setup();
		$this->privacy = Privacy_My_Way::instance();
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
	}

	private function run_tests() {
		$args = array(
			'themes' => array(
				'function' => 'filter_themes',
				'url'      => 'https://api.wordpress.org/themes/update-check/',
				'args'     =>  wp_get_themes(),
			)
		);
		$this->privacy_setup();
		Privacy_My_Way::get_instance( $args );
	}


}
