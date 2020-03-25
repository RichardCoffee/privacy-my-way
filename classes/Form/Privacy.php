<?php
/**
 *  Display the plugin options screen.
 *
 * @package Privacy_My_Way
 * @subpackage Forms
 * @since 20170222
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/privacy-my-way/blob/master/classes/Form/Privacy.php
 */
defined( 'ABSPATH' ) || exit;

class PMW_Form_Privacy extends PMW_Form_Admin {

	/**
	 * @since 20170310
	 * @var string  Form slug.
	 */
	protected $slug = 'privacy-my-way';


	/**
	 *  Constructor method.
	 *
	 * @since 20170222
	 */
	public function __construct() {
		$this->tab = $this->slug;
		add_action( 'admin_menu',              [ $this, 'add_menu_option'    ] );
		add_action( 'admin_enqueue_scripts',   [ $this, 'enqueue_theme_scripts' ] );
		add_filter( "form_text_{$this->slug}", [ $this, 'form_trans_text' ], 10, 2 );
		parent::__construct();
	}

	/**
	 *  Add the form to the Settings menu.
	 *
	 * @since 20170222
	 */
	public function add_menu_option() {
		$cap = 'update_core';
		if ( current_user_can( $cap ) ) {
			$page = __( 'Privacy My Way', 'privacy-my-way' );
			$menu = __( 'Privacy My Way', 'privacy-my-way' );
			$func = array( $this, $this->render );
			$this->hook_suffix = add_options_page( $page, $menu, $cap, $this->slug, $func );
		}
	}

	/**
	 *  Load the required scripts.
	 *
	 * @since 20170222
	 * @param string $hook  Suffix for action hook - not used here.
	 */
	public function admin_enqueue_scripts( $hook ) {
		$paths = PMW_Plugin_Paths::instance();
		wp_enqueue_style(  'privacy-form.css', $paths->get_plugin_file_uri( 'css/pmw-admin-form.css' ), null, $paths->version );
		wp_enqueue_script( 'privacy-form.js',  $paths->get_plugin_file_uri( 'js/pmw-admin-form.js' ), array( 'jquery' ), $paths->version, true );
		$this->add_localization_object( 'privacy-form.js', 'pmw_admin_form' );
	}

	/**
	 *  Load scripts in theme mode.
	 *
	 * @since 20170328
	 */
	public function enqueue_theme_scripts() {
		$paths = PMW_Plugin_Paths::instance();
		wp_enqueue_style(  'privacy-form.css', $paths->get_plugin_file_uri( 'css/pmw-theme-form.css' ), null, $paths->version );
	}

	/**
	 *  Get the privacy options layout
	 *
	 * @since 20170222
	 * @param  array $form  Passed if tabbed layout
	 * @return array        Form layout
	 */
	protected function form_layout( $form = array() ) {
		$options = new PMW_Options_Privacy;
		$form    = $options->default_form_layout();
		$form['title'] = __( 'Privacy My Way', 'privacy-my-way' );
		return $form;
	}

	/**
	 *  Translate strings
	 *
	 * @since 20170222
	 * @param array $text  Pre-set text.
	 * @param array $orig  Original text.
	 */
	public function form_trans_text( $text, $orig ) {
		$text['submit']['object']  = __( 'Privacy', 'privacy-my-way' );
		$text['submit']['subject'] = __( 'Privacy', 'privacy-my-way' );
		return $text;
	}


}
