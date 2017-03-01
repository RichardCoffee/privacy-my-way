<?php


class PMW_Plugin_Privacy extends PMW_Plugin_Plugin {

	protected $tab   = 'privacy';

	private static $privacy  = null;

	use PMW_Trait_Singleton;

	public function initialize() {

		register_deactivation_hook( $this->paths->file, array('PMW_Register_Privacy','deactivate'));
		register_uninstall_hook(    $this->paths->file, array('PMW_Register_Privacy','uninstall'));

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
		if ( is_admin() ) {
			require_once( $this->paths->plugin . '/classes/privacy.php' );
			new Privacy_My_Way;
			if ( $this->state === 'alone' ) {
				add_action( 'admin_menu', array( PMW_Form_Privacy::instance(), 'add_menu_option' ) );
#				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			} else {
				new PMW_Options_Privacy;
#				add_action( 'tcc_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}
		parent::add_actions();
	}

	public function enqueue_scripts() { }

	public function admin_menu_setup() {
		$page_title = __('Privacy My Way','tcc-privacy');
		add_options_page( $page_title, $page_title, 'update_core', $this->tab, array( $this, 'load_form_page' ) );
	}


}
