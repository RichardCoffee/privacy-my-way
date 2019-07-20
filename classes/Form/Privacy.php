<?php

class PMW_Form_Privacy extends PMW_Form_Admin {


	protected $slug = 'privacy-my-way';


	public function __construct() {
		$this->library = new PMW_Plugin_Library;
		add_action( 'admin_menu',              array( $this, 'add_menu_option'    ) );
		add_action( 'tcc_load_form_page',      array( $this, 'tcc_load_form_page' ) );
		add_filter( "form_text_{$this->slug}", array( $this, 'form_trans_text' ), 10, 2 );
		parent::__construct();
	}

	public function add_menu_option() {
		$cap = 'update_core';
		if ( current_user_can( $cap ) ) {
			$page = __( 'Privacy My Way', 'privacy-my-way' );
			$menu = __( 'Privacy My Way', 'privacy-my-way' );
			$func = array( $this, $this->render );
			$this->hook_suffix = add_options_page( $page, $menu, $cap, $this->slug, $func );
		}
	}

	public function tcc_load_form_page() {
		if ( $this->slug === $this->tab ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_theme_scripts' ) );
		}
	}

	public function admin_enqueue_scripts( $hook ) {
		$paths = plugin_paths();
		wp_enqueue_style(  'privacy-form.css', $paths->get_plugin_file_uri( 'css/pmw-admin-form.css' ), null, $paths->version );
		wp_enqueue_script( 'privacy-form.js',  $paths->get_plugin_file_uri( 'js/pmw-admin-form.js' ), array( 'jquery' ), $paths->version, true );
	}

	public function enqueue_theme_scripts() {
		$paths = plugin_paths();
		wp_enqueue_style(  'privacy-form.css', $paths->get_plugin_file_uri( 'css/pmw-theme-form.css' ), null, $paths->version );
	}

	protected function form_layout( $form = array() ) {
		$options = new PMW_Options_Privacy;
		$form    = $options->default_form_layout();
		$form['title'] = __( 'Privacy My Way', 'privacy-my-way' );
		return $form;
	}

	public function form_trans_text( $text, $orig ) {
		$text['submit']['object']  = __( 'Privacy', 'privacy-my-way' );
		$text['submit']['subject'] = __( 'Privacy', 'privacy-my-way' );
		return $text;
	} //*/

}
