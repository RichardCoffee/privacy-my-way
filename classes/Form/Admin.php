<?php
/**
 *  Abstract class to provide basic functionality for displaying admin option screens.
 *
 * @package Privacy_My_Way
 * @subpackage Forms
 * @since 20150323
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Form/Admin.php
 */
defined( 'ABSPATH' ) || exit;


abstract class PMW_Form_Admin {

	/**
	 * @since 20150926
	 * @var string  Name of screen options saved in WP dbf.
	 */
	protected $current = '';
	/**
	 * @since 20150323
	 * @var array  Controls screen layout.
	 */
	protected $form = array();
	/**
	 * @since 20150926
	 * @var array  Screen options array from WP dbf.
	 */
	protected $form_opts = array();
	/**
	 * @since 20150323
	 * @var array  Contains translated text strings.
	 */
	protected $form_text = array();
	/**
	 * @since 20160212
	 * @var string  Admin menu hook, should be set in the child class.
	 */
	protected $hook_suffix;
	/**
	 * @since 20150323
	 * @var string  Callback function for settings field.
	 */
	protected $options;
	/**
	 * @since 20150323
	 * @var string  Screen options name prefix.
	 */
	protected $prefix = 'tcc_options_';
	/**
	 * @since 20150323
	 * @var string  Name of function that registers the form.
	 */
	protected $register;
	/**
	 * @since 20150323
	 * @var string  Callback function for rendering fields.
	 */
	protected $render;
	/**
	 * @since 20150323
	 * @var string  Page slug.
	 */
	protected $slug = 'default_page_slug';
	/**
	 * @since 20151001
	 * @var string  Form tab to be shown to user.
	 */
	public $tab = 'about';
	/**
	 * @since 20150323
	 * @var string  Form type: 'single','tabbed'.
	 * @todo add 'multi'
	 */
	protected $type = 'single';
	/**
	 * @since 20150323
	 * @var string  Callback function for field validation.
	 */
	protected $validate;
	/**
	 * @since 20200421
	 * @var string  Version string used for wp_enqueue_*
	 */
	protected $version = '1.0.0';

	/**
	 * @since 20170507
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Attributes.php
	 */
	use PMW_Trait_Attributes;
	/**
	 * @since 20170330
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Logging.php
	 */
	use PMW_Trait_Logging;

	/**
	 *  Abstract method declaration for child classes.  Function should return an array.
	 *
	 * @since 20150323
	 */
	abstract protected function form_layout( $option );
	/**
	 *  Default method to provide text at top of form screen.
	 *
	 * @since 20150323
	 */
	public function description() { return ''; }

	/**
	 *  Constructor method.
	 *
	 * @since 20150323
	 */
	protected function __construct() {
		$this->screen_type();
		/**
		 *  20200314:
		 *  When I try to load the form using the load hook, the form options never get whitelisted.
		 *  Using a filter to whitelist the options doesn't work because there is no way to load the filter call, AFAIK.
		 *  TODO:  Find out why this doesn't work like I thought it would.
		 *
		//  Child class gets the hook_suffix property during 'admin_menu' hook.
		add_action( 'admin_init', [ $this, 'load_check' ] );
		 */
		add_action( 'admin_init', [ $this, 'load_form_page' ] );
	}

	/**
	 *  Load the page form on settings page only.  Using this doesn't whitelist the options.
	 *
	 * @since 20200313
	 */
	public function load_check() {
		add_action( "load-{$this->hook_suffix}", [ $this, 'load_form_page' ] );
	}

	/**
	 *  Handles setup for loading the form.  Provides a do_action call for 'tcc_load_form_page'.
	 *
	 * @since 20150926
	 */
	public function load_form_page() {
		global $plugin_page;
		if ( ( $plugin_page === $this->slug ) || ( ( $refer = wp_get_referer() ) && ( strpos( $refer, $this->slug ) ) ) ) {
			if ( in_array( $this->type, [ 'tabbed', 'multi' ] ) ) {
				if ( array_key_exists( 'tab', $_POST ) ) {
					$this->tab = sanitize_key( $_POST['tab'] );
				} else if ( array_key_exists( 'tab', $_GET ) )  {
					$this->tab = sanitize_key( $_GET['tab'] );
				} else if ( $trans = get_transient( 'PMW_TAB' ) ) {
					$this->tab = $trans;
				} else if ( defined( 'PMW_TAB' ) ) {
					$this->tab = PMW_TAB;
				}
				set_transient( 'PMW_TAB', $this->tab, ( DAY_IN_SECONDS * 5 ) );
			}
			$this->form_text();
			$this->form = $this->form_layout();
			if ( in_array( $this->type, [ 'tabbed', 'multi' ] ) && ! array_key_exists( $this->tab, $this->form ) ) {
				$this->tab = array_key_last( $this->form );
			}
			$this->determine_option();
			$this->get_form_options();
			$func = $this->register;
			$this->$func();
			do_action( 'tcc_load_form_page' );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		}
	}

	/**
	 *  Load required style and script files.  This method should be overridden by the child class.
	 *
	 * @since 20150925
	 * @param string $hook_suffix  Admin page menu option suffix - passed by WP but not used
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		wp_enqueue_media();
		wp_enqueue_style(  'admin-form.css', get_theme_file_uri( 'css/admin-form.css' ), [ 'wp-color-picker' ], $this->version );
		wp_enqueue_script( 'admin-form.js',  get_theme_file_uri( 'js/admin-form.js' ),   [ 'jquery', 'wp-color-picker' ], $this->version, true );
		$this->add_localization_object( 'admin-form.js' );
	}

	/**
	 *  Add localization object to javascript.
	 *
	 * @since 20200324
	 * @param string $slug    Slug of script to add object to.
	 * @param string $object  Name of object to add.
	 * @param string $filter  Filter to be used for the object.
	 */
	protected function add_localization_object( $slug, $object = 'tcc_admin_options', $filter = 'tcc_form_admin_options_localization' ) {
		$options = apply_filters( $filter, array() );
		if ( $options ) {
			$options = $this->normalize_options( $options, $options );
			wp_localize_script( $slug, $object, $options );
		}
	}

	/**
	 *  Ensures 'showhide' sub-arrays contain all required subscripts for the javascript.
	 *
	 * @since 20170505
	 * @param  array $new  New and improved options.
	 * @param  array $old  Old and busted options.
	 * @return array       New and improved versions of old and busted options.
	 */
	protected function normalize_options( array $new, array $old ) {
		if ( array_key_exists( 'showhide', $old ) ) {
			$new['showhide'] = array_map( [ $this, 'normalize_showhide' ], $old['showhide'] );
		}
		return $new;
	}

	/**
	 *  Provides array defaults
	 *
	 * @since 20170507
	 * @param  array $item  Item to normalize.
	 * @return array        Normalized item.
	 */
	public function normalize_showhide( array $item ) {
		$default = array(
			'origin' => null,
			'target' => null,
			'show'   => null,
			'hide'   => null,
		);
		return array_merge( $default, $item );
	}


	/**
	 *  Assigns translated text to object array
	 *
	 * @since 20150323
	 */
	private function form_text() {
		$text = array(
			'error'  => array(
				'render'    => _x( 'ERROR: Unable to locate function %s', 'string - a function name', 'privacy-my-way' ),
				'subscript' => _x( 'ERROR: Not able to locate form data subscript:  %s', 'placeholder will be an ASCII character string', 'privacy-my-way' ),
			),
			'submit' => array(
				'save'      => __( 'Save Changes', 'privacy-my-way' ),
				'object'    => __( 'Form', 'privacy-my-way' ),
				'reset'     => _x( 'Reset %s', 'placeholder is a noun, may be plural', 'privacy-my-way' ),
				'subject'   => __( 'Form', 'privacy-my-way' ),
				'restore'   => _x( 'Default %s options restored.', 'placeholder is a noun, probably singular', 'privacy-my-way' ),
			),
			'media'  => array(
				'title'     => __( 'Assign/Upload Image', 'privacy-my-way' ),
				'button'    => __( 'Assign Image', 'privacy-my-way' ),
				'delete'    => __( 'Unassign Image', 'privacy-my-way' ),
			),
		);
		// Changes based on page slug.
		$this->form_text = apply_filters( "form_text_{$this->slug}", $text, $text );
		// Changes based on the tab.
		$this->form_text = apply_filters( "fluidity_text_filter_{$this->tab}", $this->form_text, $text );
		add_filter( 'tcc_form_admin_options_localization', [ $this, 'add_form_text_localization' ] );
	}

	/**
	 *  Send form text to javascript.
	 *
	 * @since 20200420
	 * @param  array $options  Incoming localization data.
	 * @return array           Data to be passed to javascript.
	 */
	public function add_form_text_localization( array $options ) {
		return array_merge( $this->form_text, $options );
	}


	/**  Register Screen functions **/

	/**
	 *  Assign default values for callback functions.
	 *
	 * @since 20150323
	 */
	private function screen_type() {
		$this->register = 'register_' . $this->type . '_form';
		$this->render   =   'render_' . $this->type . '_form';
		$this->options  =   'render_' . $this->type . '_options';
		$this->validate = 'validate_' . $this->type . '_form';
	}

	/**
	 *  Setup for single form fields
	 *
	 * @since 20150323
	 */
	public function register_single_form() {
		register_setting( $this->current, $this->current, [ $this, $this->validate ] );
		$title = ( array_key_exists( 'title',    $this->form ) ) ? $this->form['title']    : '';
		$desc  = ( array_key_exists( 'describe', $this->form ) ) ? $this->form['describe'] : 'description';
		$desc  = ( is_array( $desc ) ) ? $desc : ( ( method_exists( $this, $desc ) ) ? [ $this, $desc ] : $desc );
		add_settings_section( $this->current, $title, $desc, $this->current );
		foreach( $this->form['layout'] as $item => $data ) {
			if ( is_string( $data ) ) continue;
			$this->register_field( $this->current, $this->current, $item, $data );
		}
	}

	/**
	 *  Setup for tabbed form fields
	 *
	 * @since 20150323
	 */
	public function register_tabbed_form() {
		$validater = ( array_key_exists( 'validate', $this->form ) ) ? $this->form['validate'] : $this->validate;
		foreach( $this->form as $key => $section ) {
			if ( ! ( (array) $section === $section ) )
				continue; // skip string variables
			if ( ! ( $section['option'] === $this->current ) )
				continue; // skip all but current screen
			$validate = ( array_key_exists( 'validate', $section ) ) ? $section['validate'] : $validater;
			$current  = ( array_key_exists( 'option', $this->form[ $key ] ) ) ? $this->form[ $key ]['option'] : $this->prefix . $key;
			register_setting( $current, $current, [ $this, $validate ] );
			$title    = ( array_key_exists( 'title',    $section ) ) ? $section['title']    : '';
			$describe = ( array_key_exists( 'describe', $section ) ) ? $section['describe'] : 'description';
			$describe = ( is_array( $describe ) ) ? $describe : [ $this, $describe ];
			add_settings_section( $current, $title, $describe, $current );
			foreach( $section['layout'] as $item => $data ) {
				$this->register_field( $current, $key, $item, $data );
			}
		}
	}

	/**
	 *  Register fields with the WP Settings API.
	 *
	 * @since 20150323
	 * @param string $option
	 * @param string $key
	 * @param string $item_id
	 * @param array  $data
	 */
	private function register_field( $option, $key, $item_id, $data ) {
		if ( ! is_array( $data ) )                     return; // skip string variables
		if ( ! array_key_exists( 'render', $data ) )   return; // skip variables without render data
		if ( in_array( $data['render'], [ 'skip' ] ) ) return; // skip variables when requested
		if ( in_array( $data['render'], [ 'array' ] ) ) {
		/*	$count = max( count( $data['default'] ), count( $this->form_opts[ $key ][ $item_id ] ) );
			for ( $i = 0; $i < $count; $i++ ) {
				$text  = $data['label'] . ' ' . ($i+1);
				$label = $this->element( 'label', [ 'for' => $item_id ], $text );
				$args  = array( 'key' => $key, 'item' => $item_id, 'num' => $i );
				//if ( $i + 1 === $count ) { $args['add'] = true; }
				add_settings_field( "{$item}_$i", $label, array( $this, $this->options ), $this->slug, $current, $args );
			} //*/
			$this->logg( 'ALERT: data[render] = array', $data );
		} else {
			$label = $this->field_label( $item_id, $data );
			$args  = array(
				'key'  => $key,
				'item' => $item_id,
			);
			add_settings_field( $item_id, $label, [ $this, $this->options ], $option, $option, $args );
		}
	}

	/**
	 *  Display label for field.
	 *
	 * @since 20150930
	 * @param string $ID    Field ID
	 * @param array  $data  Field data
	 * @return string       HTML element as a string.
	 */
	private function field_label( $ID, $data ) {
		$data  = array_merge( [ 'help' => '', 'label' => '' ], $data );
		$attrs = [ 'title' => $data['help'] ];
		switch( $data['render'] ) {
			case 'display':
			case 'radio_multiple':
				return $this->get_element( 'span', $attrs, $data['label'] );
			case 'title':
				$attrs['class'] = 'form-title';
				return $this->get_element( 'span', $attrs, $data['label'] );
			default:
				$attrs['for'] = $ID;
				return $this->get_element( 'label', $attrs, $data['label'] );
		}
		return '';
	}

	/**
	 *  Checks to make sure that field's validation callback function is callable.
	 *
	 * @since 20160228
	 * @param  array  $data  Data for item to be sterilized.
	 * @return array|string  Callback for squeaky clean data.
	 */
	private function sanitize_callback( $data ) {
		$valid_func = "validate_{$data['render']}";
		if ( is_array( $valid_func ) && method_exists( $valid_func[0], $valid_func[1] ) )  return $valid_func;
		if ( method_exists( $this, $valid_func ) )  return [ $this, $valid_func ];
		if ( function_exists( $valid_func ) )       return $valid_func;
		return 'wp_kses_post';
	}


	/**  Data functions  **/

	/**
	 *  Determine 'current' property value.
	 *
	 * @since 20150323
	 */
	private function determine_option() {
		if ( in_array( $this->type, [ 'tabbed', 'multi' ] ) ) {
			if ( array_key_exists( 'option', $this->form[ $this->tab ] ) ) {
				$this->current = $this->form[ $this->tab ]['option'];
			} else {
				$this->current = $this->prefix . $this->tab;
			}
		} else {
			$this->current = $this->prefix . $this->slug;
		}
	}

	/**
	 *  Retrieve form fields default values.
	 *
	 * @since 20150323
	 * @param string $option  Tabbed page option
	 * @param string $option  Name of tab to retrieve defaults for.
	 * @return array          Default options.
	 */
	protected function get_defaults( $option = '' ) {
		if ( empty( $this->form ) ) {
			$this->form = $this->form_layout();
		}
		if ( in_array( $this->type, [ 'single' ] ) ) {
			return $this->get_defaults_loop( $this->form['layout'] );
		} else {  //  tabbed page
			if ( array_key_exists( $option, $this->form ) ) {
				return $this->get_defaults_loop( $this->form[ $option ]['layout'] );
			} else {
				$this->logg( sprintf( $this->form_text['error']['subscript'], $option ), 'stack' );
			}
		}
		return [];
	} //*/

	/**
	 *  Loop through the layout array for the default values.
	 *
	 * @since 20200413
	 * @param  array $layout  The layout to search.
	 * @return array          Default values for the layout.
	 */
	private function get_defaults_loop( $layout ) {
		$default = array();
		foreach( $layout as $key => $item ) {
			if ( ! is_array( $item ) ) continue;
			if ( ! array_key_exists( 'default', $item ) ) continue;
			$defaults[ $key ] = $item['default'];
		}
		return $defaults;
	}

	/**
	 *  Retrieve theme/plugin option values, with default value backup.
	 *
	 * @since 20150323
	 */
	protected function get_form_options() {
		$database = get_option( $this->current, array() );
		$option   = explode( '_', $this->current );
		$defaults = $this->get_defaults( array_pop( $option ) );
		$this->form_opts = array_merge( $defaults, $database );
	}


	/**  Render Screen functions  **/

	/**
	 *  Render a non-tabbed screen
	 *
	 * @since 20150323
	 */
	public function render_single_form() {
		//  Wrap the form in a div.
		$this->tag( 'div', [ 'class' => 'wrap' ] );
			//  Show any errors.
			settings_errors();
			//  Start the form
			$this->tag( 'form', [ 'method' => 'post', 'action' => 'options.php' ] );
				//  Do actions at start of form.
				do_action( "fluid_form_admin_pre_display_{$this->current}" );
				//  Establish the form section.
				settings_fields( $this->current );
				//  Show the form section.
				do_settings_sections( $this->current );
				//  Do actions at end of form.
				do_action( "fluid_form_admin_post_display_{$this->current}" );
				//  Show the form buttons.
				$this->submit_buttons();
		//  Close the form and wrapping div.
		echo '</form></div>';
	}

	/**
	 *  Render a tabbed screen
	 *
	 * @since 20150323
	 */
	public function render_tabbed_form() {
		//  Get the active tab.
		$active_page = sanitize_key( $_GET['page'] );
		//  Wrap the form in a div.
		$this->tag( 'div', [ 'class' => 'wrap' ] );
			//  Insert div for screen icon.
			$this->element( 'div', [ 'id' => 'icon-themes', 'class' => 'icon32' ] );
			//  Show the screen title.
			$this->element( 'h1', [ 'class' => 'centered' ], $this->form['title'] );
			//  Show any error messages.
			settings_errors();
			//  Show the tab header
			$this->tag( 'h2', [ 'class' => 'nav-tab-wrapper' ] );
				//  Set the referer.
				$refer = "admin.php?page=$active_page";
				//  Loop to display tabs.
				foreach( $this->form as $key => $menu_item ) {
					if ( is_string( $menu_item ) ) continue;
					$tab_ref = "$refer&tab=$key";
					$tab_css = 'nav-tab' . ( ( $this->tab === $key ) ? ' nav-tab-active' : '' );
					$this->tag( 'a', [ 'href' => $tab_ref, 'class' => $tab_css ] );
					if ( array_key_exists( 'icon', $menu_item ) ) {
						$this->element( 'i', [ 'class' => [ 'dashicons', $menu_item['icon'] ] ] );
					}
					echo esc_html( $menu_item['title'] );
					echo '</a>';
				}
			//  Close tab header
			echo '</h2>';
			//  Show the form.
			$this->tag( 'form', [ 'method' => 'post', 'action' => 'options.php' ] );
				//  Insert current tab value into the form.
				$this->tag( 'input', [ 'type' => 'hidden', 'name' => 'tab', 'value' => $this->tab ] );
				//  Derive the current section.
				$current = ( array_key_exists( 'option', $this->form[ $this->tab ] ) ) ? $this->form[ $this->tab ]['option'] : $this->prefix . $this->tab;
				//  Do actions at start of form.
				do_action( "fluid_form_admin_pre_display_{$this->tab}" );
				//  Establish the form section.
				settings_fields( $current );
				//  Show the form section.
				do_settings_sections( $current );
				//  Do actions at end of form.
				do_action( "fluid_form_admin_post_display_{$this->tab}" );
				//  Show the form buttons.
				$this->submit_buttons( $this->form[ $this->tab ]['title'] );
		//  Close the form and wrapping div.
		echo '</form><div>';
	}

	/**
	 *  Display form submit buttons.
	 *
	 * @since 20150323
	 * @param string $title  Text for reset button.
	 */
	private function submit_buttons( $title = '' ) {
		$buttons = $this->form_text['submit'];
		$this->tag( 'p', [] );
			submit_button( $buttons['save'], 'primary', 'submit', false );
			$this->tag( 'span', [ 'style' => 'float:right;' ] );
				$object = ( empty( $title ) ) ? $buttons['object'] : $title;
				$reset  = sprintf( $buttons['reset'], $object );
				submit_button( $reset, 'secondary', 'reset', false );
		echo '</span></p>';
	}

	/**
	 *  Render field on single form
	 *
	 * @since 20150323
	 * @param array $args
	 */
	public function render_single_options( $in ) {
		extract( $in );  //  $data array( 'key' => $key, 'item' => $item, 'num' => $i );
		$data   = $this->form_opts;
		$layout = $this->form['layout'];
		$this->tag( 'div', $this->render_attributes( $layout[ $item ] ) );
			if ( ! array_key_exists( 'render', $layout[ $item ] ) ) {
				echo esc_html( $data[ $item ] );
			} else {
				$func  = 'render_' . $layout[ $item ]['render'];
				$name  = $this->current . '[' . $item . ']';
				$value = ( array_key_exists( $item, $data ) ) ? $data[ $item ] : '';
				if ( in_array( $layout[ $item ]['render'], [ 'array' ] ) ) {
					$name .= '[' . $num . ']';
					#if ( isset( $add ) && $add ) { $layout[ $item ]['add'] = true; }
					$value = ( array_key_exists( $num, $data[ $item ] ) ) ? $data[ $item ][ $num ] : '';
				}
				$field = str_replace( array( '[', ']' ), array( '_', '' ), $name );
				$args = array(
					'ID'     => $field,
					'value'  => $value,
					'layout' => $layout[ $item ],
					'name'   => $name,
				);
				add_filter( "fluid_form_layout_attributes_$item", [ $this, 'render_layout_attributes' ], 5, 2 );
				if ( method_exists( $this, $func ) ) {
					$this->$func( $args );
				} else if ( function_exists( $func ) ) {
					$func( $args );
				} else {
					$this->logg( sprintf( $this->form_text['error']['render'], $func ), $args );
				}
			}
		echo '</div>';
	}

	/**
	 *  Display fields on tabbed screens
	 *
	 * @since 20150323
	 * @param array $args  Field identificatin information.
	 */
	public function render_tabbed_options( $in ) {
		extract( $in );  //  $in array( 'key' => {group-slug}, 'item' => {item-slug} )
		$data   = $this->form_opts;
		$layout = $this->form[ $key ]['layout'];
		$this->tag( 'div', $this->render_attributes( $layout[ $item ] ) );
		if ( ! array_key_exists( 'render', $layout[ $item ] ) ) {
			echo esc_html( $data[ $item ] );
		} else {
			$func = "render_{$layout[$item]['render']}";
			$name = $this->current . "[$item]";
			if ( ! array_key_exists( $item, $data ) ) {
				$data[ $item ] = ( array_key_exists( 'default', $layout[ $item ] ) ) ? $layout[ $item ]['default'] : '';
			}
			$args = array(
				'ID'     => $item,
				'value'  => $data[ $item ],
				'layout' => $layout[ $item ],
				'name'   => $name,
			);
			add_filter( "fluid_form_layout_attributes_$item", [ $this, 'render_layout_attributes' ], 5, 2 );
			if ( method_exists( $this, $func ) ) {
				$this->$func( $args );
			} elseif ( function_exists( $func ) ) {
				$func( $args );
			} else {
				$this->logg( sprintf( $this->form_text['error']['render'], $func ) );
			}
		}
		echo '</div>';
	}

	/**
	 *  Render fields on multiple screen form.  Non-functional!!!
	 *
	 * @since 20150323
	 * @param array $args
	 */
	public function render_multi_options( $args ) {
	}

	/**
	 *  Determine field attributes for rendering.
	 *
	 * @since 20161206
	 * @param  array $layout  Layout for field to be rendered.
	 * @return array          Field rendering characteristics.
	 */
	private function render_attributes( $layout ) {
		$attrs = array();
		$attrs['class'] = ( array_key_exists( 'divcss', $layout ) ) ? $layout['divcss'] : '';
		$attrs['title'] = ( array_key_exists( 'help',   $layout ) ) ? $layout['help']   : '';
/*  This code is obsolete, but kept for reference purposes.
		if ( array_key_exists( 'showhide', $layout ) ) {
			$state = array_merge( [ 'show' => null, 'hide' => null ], $layout['showhide'] );
			$attrs['data-item'] = ( array_key_exists( 'item', $state ) ) ? $state['item'] : $state['target'];
			$attrs['data-show'] = $state['show'];
			$attrs['data-hide'] = $state['hide'];
		} */
		return $attrs;
	}

	public function render_layout_attributes( $attrs, $layout ) {
		if ( array_key_exists( 'attrs', $layout ) ) {
			$attrs = array_merge( $attrs, $layout['attrs'] );
		}
		return $attrs;
	}


	/**
	 *  Render Items functions
	 *
	 *  $data = array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name );
	 **/

	/**
	 *  Render an array
	 *
	 * @since 20150927
	 * @param array $data field information
	 * @todo needs add/delete/sort
	 */
	private function render_array( $data ) {
		$layout = $data['layout'];
		if ( ! array_key_exists( 'type', $layout ) ) $layout['type'] = 'text';
		if ( in_array( $layout['type'], [ 'image' ] ) ) {
			$this->render_image( $data );
		} else {
			$this->render_text( $data );
		}
	}

	/**
	 *  Render a checkbox field.
	 *
	 * @since 20150323
	 * @param array $data field information
	 */
	private function render_checkbox( $data ) {
		extract( $data );  //  Keys are 'ID', 'value', 'layout', 'name'
		$attrs = array(
			'type'  => 'checkbox',
			'id'    => $ID,
			'name'  => $name,
			'value' => $value,
			'onchange' => ( array_key_exists( 'change', $layout ) ) ? $layout['change'] : '',
		);
		$this->checked( $attrs, $value, true );
		$html  = $this->get_tag( 'input', $attrs );
		$html .= '&nbsp;';
		$html .= $this->get_element( 'span', [], $layout['text'] );
		$this->element( 'label', [], $html, true );
	}

	/**
	 *  Render multiple checkbox fields.
	 *
	 * @since 20170202
	 * @param array $data field information
	 */
	private function render_checkbox_multiple( $data ) {
		extract( $data );  //  Keys are 'ID', 'value', 'layout', 'name'
		if ( ! array_key_exists( 'source', $layout ) ) {
			return;
		}
		if ( array_key_exists( 'text', $layout ) ) {
			$this->element( 'div', [], $layout['text'] );
		}
		foreach( $layout['source'] as $key => $text ) {
			$attrs = array(
				'type'  => 'checkbox',
				'id'    => $ID . '-' . $key,
				'name'  => $name . '[' . $key . ']', //  "{$name}[$key]"
				'value' => $key,
			);
			$check = array_key_exists( $key, $value ) ? true : false;
			$this->checked( $attrs, $check );
			$html  = $this->get_tag( 'input', $attrs );
			$html .= '&nbsp;';
			$html .= $this->get_element( 'span',  [], $text );
			$label = $this->get_element( 'label', [], $html, true );
			$this->element( 'div', [], $label, true );
		}
	}

	/**
	 *  Render colorpicker field
	 *
	 * @since 20150927
	 * @param array $data field information
	 */
	private function render_colorpicker( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		$attrs = array(
			'type'  => 'text',
			'class' => 'form-colorpicker',
			'name'  => $name,
			'value' => $value,
			'data-default-color' => $layout['default']
		);
		$this->element( 'input', $attrs );
		$text = ( array_key_exists( 'text', $layout ) ) ? $layout['text'] : '';
		if ( $text ) {
			echo '&nbsp;';
			$this->element( 'span', [ 'class' => 'form-colorpicker-text' ], $text );
		}
	}

	/**
	 *  Display a field as text
	 *
	 * @since 20160201
	 * @param array $data  Field information.
	 */
	private function render_display( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		if ( array_key_exists( 'default', $layout ) && $value ) {
			echo esc_html( $value );
		}
		if ( array_key_exists( 'text', $layout ) ) {
			$this->element( 'span', [ ], ' ' . $layout['text'] );
		}
	}

	/**
	 *  Render a font selection field
	 *
	 * @since 20160203
	 * @param array $data field information
	 */
	private function render_font( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		$attrs = array(
			'id'       => $ID,
			'name'     => "{$name}[]",
			'multiple' => '',
			'onchange' => ( array_key_exists( 'change', $layout ) ) ? $layout['change'] : '',
		);
		$html = '';
		foreach( $layout['source'] as $key => $text ) {
			$attrs = [ 'value' => $key ];
			$this->selected( $attrs, $key, $value );
			$html .= $this->get_element( 'option', $attrs, ' ' . $key . ' ' );
		}
		$this->element( 'select', $attrs, $html, true );
		if ( array_key_exists( 'text', $data['layout'] ) ) {
			$this->element( 'span', [ ], ' ' . $data['layout']['text'] );
		}
	}

	/**
	 *  Render an image on the form
	 *
	 * @since 20150925
	 * @param array $data field information
	 */
	private function render_image( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		$media = $this->form_text['media'];
		if ( array_key_exists( 'media', $layout ) ) $media = array_merge( $media, $layout['media'] );
		$div = array(
			'data-title'  => $media['title'],
			'data-button' => $media['button'],
			'data-field'  => $ID,
		);
		$img_css = 'form-image-container' . ( ( empty( $value ) ) ? ' hidden' : '' );
		$btn_css = 'form-image-delete'    . ( ( empty( $value ) ) ? ' hidden' : '' );
		$attrs = array(
			'id'    => $ID . '_input',
			'type'  => 'text',
			'class' => 'hidden',
			'name'  => $name,
			'value' => $value,
		);
		$html  = $this->get_element( 'button', [ 'type' => 'button', 'class' => 'form-image' ], $media['button'] );
		$html .= $this->get_element( 'input', $attrs );
		$img   = $this->get_tag( 'img', [ 'id' => $ID . '_img', 'src' => $value, 'alt' => $value ] );
		$html .= $this->get_element( 'div', [ 'class' => $img_css ], $img, true );
		$html .= $this->get_element( 'button', [ 'type' => 'button', 'class' => $btn_css ], $media['delete'] );
		$this->element( 'div', $div, $html, true );
	}

	/**
	 *  Render radio field
	 *
	 * @since 20160201
	 * @param array $data field information
	 */
	private function render_radio( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		if ( ! array_key_exists( 'source', $layout ) ) return;
		$base_attrs = array(
			'type'     => 'radio',
			'name'     => $name,
			'onchange' => ( array_key_exists( 'change', $layout ) ) ? $layout['change'] : '',
		);
		$html = '';
		if ( array_key_exists( 'text', $layout ) ) {
			$uniq  = uniqid();
			$html .= $this->get_element( 'div', [ 'id' => $uniq ], $layout['text'] );
			$base_attrs['aria-describedby'] = $uniq;
		}
		foreach( $layout['source'] as $key => $text ) {
			$radio_attrs = $base_attrs;
			$radio_attrs['value'] = $key;
			$this->checked( $radio_attrs, $value, $key );
			$item = $this->get_tag( 'input', $radio_attrs );
			if ( array_key_exists( 'src-html', $layout ) ) {
				$item .= wp_kses( $text, pmw()->kses() );
			} else {
				$item .= esc_html( $text );
			}
			if ( array_key_exists( 'extra_html', $layout ) && array_key_exists( $key, $layout['extra_html'] ) ) {
				$item .= wp_kses( $layout['extra_html'][ $key ], pmw()->kses() );
			}
			$label = $this->get_element( 'label', [], $item,  true );
			$html .= $this->get_element( 'div',   [], $label, true );
		}
		if ( array_key_exists( 'postext', $layout ) ) {
			$html .= $this->get_element( 'div', [], $layout['postext'] );
		}
		$this->element( 'div', [], $html, true );
	}

	/**
	 *  Render multiple radio fields - this has limited use - only displays yes/no radios
	 *
	 * @since 20170202
	 * @param array $data field information
	 */
	private function render_radio_multiple( $data ) {
		extract( $data );   //  Extracts 'ID', 'value', 'layout', 'name'
		if ( ! array_key_exists( 'source', $layout ) ) return;
		$pre_css   = ( array_key_exists( 'textcss', $layout ) ) ? $layout['textcss'] : '';
		$pre_text  = ( array_key_exists( 'text',    $layout ) ) ? $layout['text']    : '';
		$post_text = ( array_key_exists( 'postext', $layout ) ) ? $layout['postext'] : '';
		$preset    = ( array_key_exists( 'preset',  $layout ) ) ? $layout['preset']  : 'no';
		//  Pre-Text
		$html = $this->get_element( 'div', [ 'class' => $pre_css ], $pre_text );
		//  Radio labels
		$label  = $this->get_element( 'span', [ 'class' => 'radio-multiple-yes' ], __( 'Yes', 'privacy-my-way' ) );
		$label .= '&nbsp;';
		$label .= $this->get_element( 'span', [ 'class' => 'radio-multiple-no'  ], __( 'No',  'privacy-my-way' ) );
		$html  .= $this->get_element( 'div',  [ 'class' => 'radio-multiple-header' ], $label, true );
		//  Radio buttons
		foreach( $layout['source'] as $key => $text ) {
			$check  = ( array_key_exists( $key, $value ) ) ? $value[ $key ] : $preset;
			//  Yes radio
			$yes = array(
				'type'  => 'radio',
				'value' => 'yes',
				'class' => 'radio-multiple-list radio-multiple-list-yes',
				'name'  => $name . '[' . $key . ']',
			);
			$this->checked( $yes, $check, 'yes' );
			$item  = $this->get_element( 'input', $yes );
			$item .= '&nbsp;';
			//  No radio
			$no = array(
				'type'  => 'radio',
				'value' => 'no',
				'class' => 'radio-multiple-list radio-multiple-list-no',
				'name'  => $name . '[' . $key . ']',
			);
			$this->checked( $no, $check, 'no' );
			$item .= $this->get_element( 'input', $no );
			$item .= $this->get_element( 'span', [ 'class' => 'radio-multiple-list-text' ], wp_kses( $text, pmw()->kses() ), true );
			$label = $this->get_element( 'label', [], $item, true );
			$html .= $this->get_element( 'div', [ 'class' => 'radio-multiple-list-item' ], $label, true );
		}
		if ( array_key_exists( 'postext', $layout ) ) {
			$html .= $this->element( 'div', [ 'class' => 'radio-multiple-post-text' ], $post_text );
		}
		$this->element( 'div', [ 'class' => 'radio-multiple-div' ], $html, true );
	}

	/**
	 *  Render a select field.
	 *
	 * @since 20150323
	 * @param array $data  Field information.
	 */
	private function render_select( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		if ( ! array_key_exists( 'source', $layout ) ) return;
		if ( array_key_exists( 'text', $layout ) ) {
			$this->element( 'div', [ 'class' => 'form-select-text' ], $layout['text'] );
		}
		$attrs = array(
			'id'   => $ID,
			'name' => $name
		);
		$helper = 'selected';
		if ( strpos( $name, '[]' ) ) {
			$attrs['multiple'] = 'multiple';
			$helper = 'selected_m';
		}
		if ( array_key_exists( 'change', $layout ) ) {
			$attrs['onchange'] = $layout['change'];
		}
		$attrs = apply_filters( "fluid_form_layout_attributes_$ID", $attrs, $layout );
		$this->tag( 'select', $attrs );
			$source_func = $layout['source'];
			if ( is_array( $source_func ) ) {
				foreach( $source_func as $key => $text ) {
					$attrs = [ 'value' => $key ];
					$this->$helper( $attrs, $key, $value );
					$this->element( 'option', $attrs, ' ' . $text . ' ' );
				}
			} else if ( method_exists( $this, $source_func ) ) {
				$this->$source_func( $value );
			} else if ( function_exists( $source_func ) ) {
				$source_func( $value );
			}
		echo '</select>';
	}

	/**
	 *  Render a field with multiple selects
	 *
	 * @since 20170228
	 * @param array $data  Field information.
	 */
	private function render_select_multiple( $data ) {
		//  Insure the name has brackets for array values.
		if ( ! strpos( $data['name'], '[]' ) ) $data['name'] .= '[]';
		//  Add directions if none are provided
		if ( ! array_key_exists( 'help', $data['layout'] ) ) {
			//  Check for the attributes array.
			if ( ! array_key_exists( 'attrs', $data['layout'] ) ) $data['layout']['attrs'] = array();
			//  Add the directions unless something is already there.
			if ( ! array_key_exists( 'title', $data['layout']['attrs'] ) ) {
				$data['layout']['attrs']['title'] = __( "Utilize the 'ctrl+click' combo to choose multiple items.", 'privacy-my-way' );
			}
		}
		$this->render_select( $data );
	}

	/**
	 *  Render spinner
	 *
	 * @since 20170126
	 * @param array $data field information
	 */
	private function render_spinner( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		if ( array_key_exists( 'text', $layout ) ) {
			$this->element( 'div', [], $layout['text'] );
		}
		$attrs = array(
			'id'    => $ID,
			'name'  => $name,
			'value' => $value,
		);
		$attrs = array_merge( $attrs, $this->attributes_spinner( $layout ) );
		$attrs = apply_filters( "fluid_form_layout_attributes_$ID", $attrs, $layout );
		$this->element( 'input', $attrs );
		if ( array_key_exists( 'postext', $layout ) ) {
			$this->element( 'div', [], $layout['postext'] );
		}
	}

	/**
	 *  Provide spinner defaults.
	 *
	 * @since 20200411
	 * @param array $layout  The item to provide defaults for.
	 * @return array         Attributes for the spinner input element.
	 */
	private function attributes_spinner( $layout ) {
		$attrs = array(
			'type'  => 'number',
			'class' => 'small-text',
#			'min'   => '1',
			'step'  => '1',
		);
		return $attrs;
	}

	/**
	 *  Render text on the form
	 *
	 * @since 20150323
	 * @param array $data  Field information.
	 */
	private function render_text( $data ) {
		extract( $data );  //  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		if ( array_key_exists( 'text', $layout ) ) {
			$this->element( 'p', [], ' ' . $layout['text'] );
		}
		$attrs = array(
			'type'  => 'text',
			'id'    => $ID,
			'class' => ( array_key_exists( 'class', $layout ) ) ? $layout['class'] : 'regular-text',
			'name'  => $name,
			'value' => $value,
			'title' => ( array_key_exists( 'help', $layout ) ) ? $layout['help'] : '',
			'placeholder' => ( array_key_exists( 'place',  $layout ) ) ? $layout['place']  : '',
			'onchange'    => ( array_key_exists( 'change', $layout ) ) ? $layout['change'] : '',
		);
		$this->element( 'input', $attrs );
		if ( array_key_exists( 'stext', $layout ) ) {
			$this->element( 'span', [], ' ' . $layout['stext'] );
		}
		if ( array_key_exists( 'postext', $layout ) ) {
			$this->element( 'p', [], $layout['postext'] );
		}
	}

	/**
	 *  Render color picker field with text
	 *
	 * @since 20170809
	 * @param array $data field information
	 */
	private function render_text_color( $data ) {
		$this->render_text( $data );
		$basic = explode( '[', $data['name'] );
		$index = substr( $basic[1], 0, -1 ) . '_color';
		$data['name']  = $basic[0] . '[' . $index . ']';
		$data['value'] = ( array_key_exists( $index, $this->form_opts ) ) ? $this->form_opts[ $index ] : $data['layout']['color'];
		$data['layout']['default'] = $data['layout']['color'];
		$data['layout']['text']    = '';
		$this->render_colorpicker( $data );
	}

	/**
	 *  Alias for render_display()
	 *
	 * @since 20160201
	 * @param array $data field information
	 */
	private function render_title( $data ) {
		$this->render_display( $data );
	}

	/**  Validate functions  **/

	/**
	 *  Handles validation for single form fields
	 *
	 * @since 20150323
	 * @param array $input input field list
	 * @return array validated fields
	 * @todo notify user of missing required fields
	 */
	public function validate_single_form( $input ) {
		$output = $this->get_defaults();
		if ( array_key_exists( 'reset', $_POST ) ) {
			$object = ( array_key_exists( 'title', $this->form ) ) ? $this->form['title'] : $this->form_test['submit']['object'];
			$string = sprintf( $this->form_text['submit']['restore'], $object );
			add_settings_error( $this->slug, 'restore_defaults', $string, 'updated fade' );
		} else {
			foreach( $input as $ID => $data ) {
				$item = $this->form['layout'][ $ID ];
				$multiple = array( 'array', 'radio_multiple' );
				if ( in_array( $item['render'], $multiple ) ) {
					$item['render'] = ( array_key_exists( 'type', $item ) ) ? $item['type'] : 'text';
					$vals = array();
					foreach( $data as $key => $indiv ) {
						$vals[ $key ] = $this->do_validate_function( $indiv, $item );
					}
					$output[ $ID ] = $vals;
				} else if ( in_array( $item['render'], [ 'checkbox' ] ) ) {
					$output[ $ID ] = true;
				} else {
					$output[ $ID ] = $this->do_validate_function( $data, $item );
				}
			}
			// check for required fields FIXME: notify user
			foreach( $this->form['layout'] as $ID => $item ) {
				if ( is_array( $item ) && array_key_exists( 'require', $item ) && $item['require'] ) {
					if ( empty( $output[ $ID ] ) ) {
						$output[ $ID ] = $item['default'];
					}
				}
			}
			$diff = array_diff_key( $output, $input );
			foreach( $diff as $key => $data ) {
				$item = ( array_key_exists( $key, $this->form['layout'] ) ) ? $this->form['layout'][ $key ] : array();
				if ( array_key_exists( 'render', $item ) && in_array( $item['render'], [ 'checkbox' ] ) ) {
					$output[ $key ] = false;
				}
			}
		}
		return apply_filters( "{$this->slug}_validate_settings", $output, $input );
	}

	/**
	 *  Handles validation for tabbed form fields.
	 *
	 * @since 20150927
	 * @param  array $input  Incoming field values.
	 * @return array         Validated field values
	 */
	public function validate_tabbed_form( $input ) {
		$option = sanitize_key( $_POST['tab'] );
		$output = $this->get_defaults( $option );
		if ( array_key_exists( 'reset', $_POST ) ) {
			$object = ( array_key_exists( 'title', $this->form[ $option ] ) ) ? $this->form[ $option ]['title'] : $this->form_test['submit']['object'];
			$string = sprintf( $this->form_text['submit']['restore'], $object );
			add_settings_error( $this->slug, 'restore_defaults', $string, 'updated fade' );
		} else {
			foreach( $input as $key => $data ) {
				$item = ( array_key_exists( $key, $this->form[ $option ]['layout'] ) ) ? $this->form[ $option ]['layout'][ $key ] : array();
				if ( array_key_exists( 'render', $item ) && in_array( $item['render'], [ 'checkbox' ] ) ) {
					$output[ $key ] = true;
				} else {
					$output[ $key ] = $this->do_validate_function( $data, $item );
				}
			}
			$diff = array_diff_key( $output, $input );
			foreach( $diff as $key => $data ) {
				$item = ( array_key_exists( $key, $this->form[ $option ]['layout'] ) ) ? $this->form[ $option ]['layout'][ $key ] : array();
				if ( array_key_exists( 'render', $item ) && in_array( $item['render'], [ 'checkbox' ] ) ) {
					$output[ $key ] = false;
				}
			}
		}
		return apply_filters( "{$this->current}_validate_settings", $output, $input );
	}

	/**
	 *  Execute validation callback function
	 *
	 * @since 20150323
	 * @param mixed $input data being validated
	 * @param array $item field information
	 */
	private function do_validate_function( $input, $item ) {
		if ( empty( $item['render'] ) ) {
			$item['render'] = 'non_existing_render_type';
		}
		$func = ( array_key_exists( 'validate', $item ) ) ? $item['validate'] : 'validate_' . $item['render'];
		if ( method_exists( $this, $func ) ) {
			$output = $this->$func( $input, $item );
		} else if ( function_exists( $func ) ) {
			$output = $func( $input, $item );
		} else { // FIXME:  test for data type?
			$output = $this->validate_text( $input, $item );
			$this->logg( 'missing validation function: ' . $func, $item, $input );
		}
		return $output;
	}

	/**
	 *  Validate checkbox field
	 *
	 * @since 20180307
	 * @param string $input
	 * @return string
	 */
	private function validate_checkbox( $input, $item = array() ) {
		return ( $input ) ? true : false;
	}

	/**
	 *  Validate multiple checkbox field
	 *
	 * @since 20180307
	 * @param string $input
	 * @return string
	 */
	private function validate_checkbox_multiple( $input, $item ) {
		return array_map( array( $this, 'validate_checkbox' ), $input );
	}

	/**
	 *  Validate colorpicker field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_colorpicker( $input, $item ) {
		return ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $input ) ) ? $input : '';
	}

	/**
	 *  Validate font field value
	 *
	 * @since 20170228
	 * @param string $input
	 * @return string
	 */
	private function validate_font( $input, $item ) {
		$this->logging_force = true;
		$this->logg( $input ); // TODO: compare value to available fonts
		return $input; // FIXME NOW!
	}

	/**
	 *  Validate image field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_image( $input, $item ) {
		return apply_filters( 'pre_link_image', $input );
	}

	/**
	 *  Validate content field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_post_content( $input, $item ) {
		return wp_kses_post( $input );
	}

	/**
	 *  Validate radio field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_radio( $input, $item ) {
		$input = sanitize_key( $input );
		return ( array_key_exists( $input, $item['source'] ) ? $input : $item['default'] );
	}

	/**
	 *  Validate multiple radio fields
	 *
	 * @since 20170228
	 * @param string $input
	 * @return string
	 */
	private function validate_radio_multiple( $input, $item ) {
		return $this->validate_radio( $input );
	}

	/**
	 *  Validate select field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_select( $input, $item ) {
		$input = sanitize_text_field( $input );
		return ( in_array( $input, $item['source'] ) || array_key_exists( $input, $item['source'] ) ) ? $input : $item['default'];
	}

	/**
	 *  Validate select field with multiple values
	 *
	 * @since 20170228
	 * @param string $input
	 * @return string
	 */
	private function validate_select_multiple( $input, $item ) {
		foreach( $input as $key => $choice ) {
			$input[ $key ] = $this->validate_select( $choice, $item );
		}
		return array_unique( $input );
	}

	/**
	 *  Validate spinner field value
	 *
	 * @since 20170305
	 * @param string $input
	 * @return string
	 */
	private function validate_spinner( $input, $item ) {
		$attrs = $this->attributes_spinner( $item );
		if ( array_key_exists( 'step', $attrs ) && ( $attrs['step'] % 1 ) ) {
			$input = floatval( $input );
		} else {
			$input = intval( $input );
		}
		if ( array_key_exists( 'min', $attrs ) ) $input = max( $input, $attrs['min'] );
		if ( array_key_exists( 'max', $attrs ) ) $input = min( $input, $attrs['max'] );
		return "$input";
	}

	/**
	 *  Validate text field value.
	 *
	 * @since 20170305
	 * @param string $input
	 * @return string
	 */
	protected function validate_text( $input, $item = array() ) {
		return wp_kses_data( $input );
	}

	/**
	 *  Validate text color field value.  Allows color text string, ie 'red', 'blue', 'black', etc.
	 *
	 * @since 20160910
	 * @param string $input
	 * @return string
	 */
	private function validate_text_color( $input, $item ) {
		return $this->validate_text( $input );
	}

	/**
	 *  Validate url field value.
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_url( $input, $item ) {
		return apply_filters( 'pre_link_url', $input );
	}


} # end of PMW_Form_Admin class


/**  These are compatibility functions  **/

/**
 *  array_key_first() introduced in PHP 7.3.0
 *
 * @since 20200315
 * @param array $arr  Input array.
 * @return string     First key of the array.
 */
if ( ! function_exists( 'array_key_first' ) ) {
	function array_key_first( array $arr ) {
		foreach( $arr as $key => $item ) return $key;
		return null;
	}
}

/**
 *  array_key_last() introduced in PHP 7.3.0
 *
 * @since 20200315
 * @param array $arr  Input array.
 * @return string     Last key of the array.
 */
if ( ! function_exists( 'array_key_last' ) ) {
	function array_key_last( array $arr ) {
		return array_key_first( array_reverse( $arr, true ) );
	}
}
