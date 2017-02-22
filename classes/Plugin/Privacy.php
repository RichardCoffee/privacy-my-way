<?php


class TCC_Plugin_Privacy extends TCC_Plugin_Basic {

	private   $agent;
	private   $options;	#	options screen
	protected $tab   = 'privacy';

	private static $privacy  = null;
	public  static $translat = array();

	use TCC_Trait_Singleton;

	protected function __construct( $args = array() ) {

		$defaults = array('dir'     => TCC_PRIVACY_DIR,
		                  'file'    => TCC_PRIVACY_FILE,
		                  'plugin'  => dirname( plugin_basename( TCC_PRIVACY_FILE ) ),
		                  'url'     => plugin_dir_url( TCC_PRIVACY_FILE ),
		                  'version' => TCC_PRIVACY_VERSION ) );
		$args = array_merge( $defaults, $args );
		parent::__construct( $args );

		#	parent class creates TCC_STATE_* definitions
		switch ( $this->state ) {
			case TCC_STATE_PLUGIN:
				add_action( 'tcc_theme_options_loaded', array( $this, 'initialize' ) );
				break;
			case TCC_STATE_ALONE:
				$this->settings = ''; // FIXME: needs correct url
			case TCC_STATE_THEME:
			default:
				add_action( 'plugins_loaded', array( $this, 'initialize' ), 100 );
		}
	}

	public function initialize() {

		register_deactivation_hook( TCC_PRIVACY_FILE, array('TCC_Register_Privacy','deactivate'));
		register_uninstall_hook(    TCC_PRIVACY_FILE, array('TCC_Register_Privacy','uninstall'));

		$args = array(
			'text_domain' => 'Text Domain',
			'lang_dir'    => 'Domain Path',
		);
		$data = get_file_data( TCC_PRIVACY_FILE, $args );
		load_plugin_textdomain( $data['text_domain'], false, $this->paths->plugin . $data['lang_dir'] );

		$this->options = new TCC_Options_Privacy;

		$this->add_actions();
		$this->add_filters();

		if ( is_admin() ) {
			$this->check_update();
		}

	}

	public function add_actions() {
		if ( is_admin() ) {
			require_once( $this->paths->plugin . '/classes/privacy.php' );
			new Privacy_My_Way;
		} else {
#			add_action( 'tcc_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
		parent::add_actions();
	}

	public function add_filters() {
		if ( is_admin() ) {
			if ( $this->state === TCC_STATE_PLUGIN ) {

			}
		}
		parent::add_filters();
	}

	public function enqueue_scripts() {

	}


}
