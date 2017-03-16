<?php


class PMW_Plugin_Privacy extends PMW_Plugin_Plugin {


	protected $privacy = null;
	protected $setting = 'options-general.php?page=privacy';
	protected $tab     = 'privacy';

	use PMW_Trait_Singleton;


	public function initialize() {

		if ( ( ! PMW_Register_Privacy::php_version_check() ) || ( ! PMW_Register_Privacy::wp_version_check() ) ) {
			return;
		}

		register_deactivation_hook( $this->paths->file, array( 'PMW_Register_Privacy', 'deactivate' ) );
		register_uninstall_hook(    $this->paths->file, array( 'PMW_Register_Privacy', 'uninstall'  ) );

		$args = array(
			'text_domain' => 'Text Domain',
			'lang_dir'    => 'Domain Path',
		);
		$data = get_file_data( $this->paths->file, $args );
		load_plugin_textdomain( $data['text_domain'], false, $this->paths->plugin . $data['lang_dir'] );

		$this->add_actions();
		$this->add_filters();

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

	public function enqueue_scripts() { }

	public function add_privacy_filters( $locale = '' ) {
		if ( ! function_exists( 'random_int' ) ) {
			# PHP 7.0 compatibility
			require_once( $this->paths->dir . 'assets/random_compat/lib/random.php' );
		}
		include_once( $this->paths->dir . 'classes/privacy.php' );
		$this->privacy = Privacy_My_Way::instance();
		return $locale;
	}

	public function add_privacy_options( $options ) {
		$this->setting = 'admin.php?page=fluidity_options&tab=privacy';
		$options['Privacy'] = new PMW_Options_Privacy;
		return $options;
	}


}
