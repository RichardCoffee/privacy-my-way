<?php


class PMW_Form_Privacy extends PMW_Form_Admin {

	protected $slug    = 'privacy';

	use PMW_Trait_Singleton;

	protected function __construct() {
		add_filter( "form_text_{$this->slug}", array( $this, 'form_trans_text' ), 10, 2 );
		parent::__construct();
	}

	public function add_menu_option() {
		$cap = 'update_core';
		if ( current_user_can( $cap ) ) {
			$page = __( 'Privacy My Way', 'tcc-privacy' );
			$menu = __( 'Privacy My Way', 'tcc-privacy' );
			$func = array( $this, $this->render );
			$this->hook_suffix = add_options_page( $page, $menu, $cap, $this->slug, $func );
		}
	}

	public function enqueue_scripts() {
		$paths = PMW_Plugin_Paths::instance();
		wp_register_style(  'privacy-form.css', $paths->get_plugin_file_uri( 'css/admin-form.css' ), null, $paths->version );
		wp_register_script( 'privacy-form.js',  $paths->get_plugin_file_uri( 'js/admin-form.js' ), array( 'jquery' ), $paths->version, true );
		wp_enqueue_style(   'privacy-form.css' );
		wp_enqueue_script(  'privacy-form.js' );
	}

	protected function form_layout( $form = array() ) {
		$options = new PMW_Options_Privacy;
		$form    = $options->default_form_layout();
		$form['title'] = __( 'Privacy My Way', 'tcc-privacy' );
		return $form;
	}

	public function form_trans_text( $text, $orig ) {
		$text['submit']['object']  = __( 'Privacy', 'tcc-privacy' );
		$text['submit']['subject'] = __( 'Privacy', 'tcc-privacy' );
		return $text;
	} //*/

}
