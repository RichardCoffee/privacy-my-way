<?php
/**
 *  Abstract class to provide basic functionality for displaying admin option screens
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
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Attributes.php
	 */
	use PMW_Trait_Attributes;
	/**
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Logging.php
	 */
	use PMW_Trait_Logging;

	/**
	 *  Abstract method declaration for child classes.  Function should return an array.
	 *
	 * @since 20150323
	 * @used-by PMW_Form_Admin::load_form_page()
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
	 *  Load required style and script files.  Provides filter 'tcc_form_admin_options_localization' to allow child classes to add javascript variables.
	 *
	 * @since 20150925
	 * @param string $hook_suffix  Admin page menu option suffix - passed by WP but not used
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		wp_enqueue_media();
		wp_enqueue_style(  'admin-form.css', get_theme_file_uri( 'css/admin-form.css' ), [ 'wp-color-picker' ] );
		wp_enqueue_script( 'admin-form.js',  get_theme_file_uri( 'js/admin-form.js' ),   [ 'jquery', 'wp-color-picker' ], false, true );
		$this->add_localization_object( 'admin-form.js' );
	}

	/**
	 *  Add localization object to javascript.
	 *
	 * @since 20200324
	 * @param string $slug    Slug of script to add object to.
	 * @param string $object  Name of object to add.
	 * @param string $filter  Filter to used for the object.
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
	protected function normalize_options( $new, $old ) {
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
	public function normalize_showhide( $item ) {
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
				'subscript' => _x( 'ERROR: Not able to locate form data subscript:  %s', 'placeholder will be an ASCII character string', 'privacy-my-way' )
			),
			'submit' => array(
				'save'      => __( 'Save Changes', 'privacy-my-way' ),
				'object'    => __( 'Form', 'privacy-my-way' ),
				'reset'     => _x( 'Reset %s', 'placeholder is a noun, may be plural', 'privacy-my-way' ),
				'subject'   => __( 'Form', 'privacy-my-way' ),
				'restore'   => _x( 'Default %s options restored.', 'placeholder is a noun, probably singular', 'privacy-my-way' )
			),
			'media'  => array(
				'title'     => __( 'Assign/Upload Image', 'privacy-my-way' ),
				'button'    => __( 'Assign Image', 'privacy-my-way' ),
				'delete'    => __( 'Unassign Image', 'privacy-my-way' )
			)
		);
		$this->form_text = apply_filters( 'form_text_' . $this->slug, $text, $text );
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
			if ( ! ( (array)$section === $section ) )
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
	 *  Register fields with the WP Settings API
	 *
	 * @since 20150323
	 */
	private function register_field( $option, $key, $itemID, $data ) {
		if ( ! is_array( $data ) )                     return; // skip string variables
		if ( ! array_key_exists( 'render', $data ) )   return; // skip variables without render data
		if ( in_array( $data['render'], [ 'skip' ] ) ) return; // skip variables when needed
		if ( in_array( $data['render'], [ 'array' ] ) ) {
/*			$count = max( count( $data['default'] ), count( $this->form_opts[ $key ][ $itemID ] ) );
			for ( $i = 0; $i < $count; $i++ ) {
				$label  = "<label for='$itemID'>{$data['label']} ".($i+1)."</label>";
				$args   = array( 'key' => $key, 'item' => $itemID, 'num' => $i );
#				if ( $i + 1 === $count ) { $args['add'] = true; }
				add_settings_field( "{$item}_$i", $label, array( $this, $this->options ), $this->slug, $current, $args );
			} //*/
			$this->log( 'ALERT: data[render] = array', $data );
		} else {
			$label = $this->field_label( $itemID, $data );
			$args  = [ 'key' => $key, 'item' => $itemID ];
			add_settings_field( $itemID, $label, [ $this, $this->options ], $option, $option, $args );
		}
	}

	/**
	 *  Display label for field
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
	 * @param  array  $data  Data to sterilize.
	 * @return string        Squeaky clean data.
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
	 *  Determine 'current' property value
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
		$defaults = array();
		if ( in_array( $this->type, [ 'single' ] ) ) {
			foreach( $this->form['layout'] as $ID => $item ) {
				if ( is_string( $item ) || empty( $item['default'] ) ) {
					continue;
				}
				$defaults[ $ID ] = $item['default'];
			}
		} else {  //  tabbed page
			if ( array_key_exists( $option, $this->form ) ) {
				foreach( $this->form[ $option ]['layout'] as $key => $item ) {
					if ( empty( $item['default'] ) ) {
						continue;
					}
					$defaults[ $key ] = $item['default'];
				}
			} else {
				$this->logg( sprintf( $this->form_text['error']['subscript'], $option ), 'stack' );
			}
		}
		return $defaults;
	} //*/

	/**
	 *  Retrieve theme/plugin option values
	 *
	 * @since 20150323
	 */
	private function get_form_options() {
		$this->form_opts = get_option( $this->current );
		if ( empty( $this->form_opts ) ) {
			$option = explode( '_', $this->current );
			$this->form_opts = $this->get_defaults( $option[2] );
			add_option( $this->current, $this->form_opts );
		}
	}


	/**  Render Screen functions  **/

	/**
	 *  Render a non-tabbed screen
	 *
	 * @since 20150323
	 */
	public function render_single_form() { ?>
		<div class="wrap">
			<?php settings_errors(); ?>
			<form method="post" action="options.php"><?php
				do_action( 'form_admin_pre_display_' . $this->current );
				settings_fields( $this->current );
				do_settings_sections( $this->current );
				do_action( 'form_admin_post_display_' . $this->current );
				$this->submit_buttons(); ?>
			</form>
		</div><?php //*/
	}

	/**
	 *  Render a tabbed screen
	 *
	 * @since 20150323
	 */
	public function render_tabbed_form() {
		$active_page = sanitize_key( $_GET['page'] ); ?>
		<div class="wrap"><?php
			$this->element( 'div', [ 'id' => 'icon-themes', 'class' => 'icon32' ] );
			$this->element( 'h1', [ 'class' => 'centered' ], $this->form['title'] );
			settings_errors(); ?>
			<h2 class="nav-tab-wrapper"><?php
				$refer = "admin.php?page=$active_page";
				foreach( $this->form as $key => $menu_item ) {
					if ( is_string( $menu_item ) ) continue;
					$tab_ref = "$refer&tab=$key";
					$tab_css = 'nav-tab' . ( ( $this->tab === $key ) ? ' nav-tab-active' : '' );
					$this->tag( 'a', [ 'href' => $tab_ref, 'class' => $tab_css ] );
						if ( ! empty( $menu_item['icon'] ) ) {
							$this->element( 'i', [ 'class' => [ 'dashicons', $menu_item['icon'] ] ] );
						}
						e_esc_html( $menu_item['title'] );
					echo '</a>';
				} ?>
			</h2>
			<form method="post" action="options.php"><?php
				$this->tag( 'input', [ 'type' => 'hidden', 'name' => 'tab', 'value' => $this->tab ] );
				$current = ( array_key_exists( 'option', $this->form[ $this->tab ] ) ) ? $this->form[ $this->tab ]['option'] : $this->prefix . $this->tab;
				do_action( "form_admin_pre_display_{$this->tab}" );
				settings_fields( $current );
				do_settings_sections( $current );
				do_action( "form_admin_post_display_{$this->tab}" );
				$this->submit_buttons( $this->form[ $this->tab ]['title'] ); ?>
			</form>
		<div><?php //*/
	}

	/**
	 *  Display form submit buttons.
	 *
	 * @since 20150323
	 * @param string $title  Text for reset button.
	 */
	private function submit_buttons( $title = '' ) {
		$buttons = $this->form_text['submit']; ?>
		<p><?php
			submit_button( $buttons['save'], 'primary', 'submit', false ); ?>
			<span style='float:right;'><?php
				$object = ( empty( $title ) ) ? $buttons['object'] : $title;
				$reset  = sprintf( $buttons['reset'], $object );
				submit_button( $reset, 'secondary', 'reset', false ); ?>
			</span>
		</p><?php
	}

	/**
	 *  Render field on single form
	 *
	 * @since 20150323
	 * @param array $args
	 */
	public function render_single_options( $args ) {
		extract( $args );  #  array( 'key' => $key, 'item' => $item, 'num' => $i );
		$data   = $this->form_opts;
		$layout = $this->form['layout'];
		$this->tag( 'div', $this->render_attributes( $layout[ $item ] ) );
			if ( empty( $layout[ $item ]['render'] ) ) {
				e_esc_html( $data[ $item ] );
			} else {
				$func  = 'render_' . $layout[ $item ]['render'];
				$name  = $this->current . '[' . $item . ']';
				$value = ( array_key_exists( $item, $data ) ) ? $data[ $item ] : '';
				if ( $layout[ $item ]['render'] === 'array' ) {
					$name .= '[' . $num . ']';
					#if ( isset( $add ) && $add ) { $layout[ $item ]['add'] = true; }
					$value = ( array_key_exists( $num, $data[ $item ] ) ) ? $data[ $item ][ $num ] : '';
				}
				$field = str_replace( array( '[', ']' ), array( '_', '' ), $name );
				$fargs = array(
					'ID'     => $field,
					'value'  => $value,
					'layout' => $layout[ $item ],
					'name'   => $name,
				);
				if ( method_exists( $this, $func ) ) {
					$this->$func( $fargs );
				} else if ( function_exists( $func ) ) {
					$func( $fargs );
				} else {
					$this->logg( sprintf( $this->form_text['error']['render'], $func ) );
				}
			} ?>
		</div><?php
	}

	/**
	 *  Display fields on tabbed screens
	 *
	 * @since 20150323
	 * @param array $args  Field identificatin information
	 */
	public function render_tabbed_options( $args ) {
		extract( $args );  #  $args = array( 'key' => {group-slug}, 'item' => {item-slug} )
		$data   = $this->form_opts;
		$layout = $this->form[ $key ]['layout'];
		$this->tag( 'div', $this->render_attributes( $layout[ $item ] ) );
		if ( empty( $layout[ $item ]['render'] ) ) {
			e_esc_html( $data[$item] );
		} else {
			$func = "render_{$layout[$item]['render']}";
			$name = $this->current . "[$item]";
			if ( ! array_key_exists( $item, $data ) ) {
				$data[ $item ] = ( empty( $layout[ $item ]['default'])) ? '' : $layout[ $item ]['default'];
			}
			$fargs = array(
				'ID'     => $item,
				'value'  => $data[ $item ],
				'layout' => $layout[ $item ],
				'name'   => $name
			);
			if ( method_exists( $this, $func ) ) {
				$this->$func( $fargs );
			} elseif ( function_exists( $func ) ) {
				$func( $fargs );
			} else {
				$this->log( sprintf( $this->form_text['error']['render'], $func ) );
			}
		}
		echo "</div>"; //*/
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
		$attrs['class'] = ( ! empty( $layout['divcss'] ) ) ? $layout['divcss'] : '';
		$attrs['title'] = ( array_key_exists( 'help', $layout ) )     ? $layout['help']   : '';
		if ( ! empty( $layout['showhide'] ) ) {
			$state = array_merge( [ 'show' => null, 'hide' => null ], $layout['showhide'] );
			$attrs['data-item'] = ( array_key_exists( 'item', $state ) ) ? $state['item'] : $state['target'];
			$attrs['data-show'] = $state['show'];
			$attrs['data-hide'] = $state['hide'];
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
		extract( $data );  //  Extracts 'ID', 'value', 'layout', and 'name'.
		if ( ! array_key_exists( 'type', $layout ) ) $layout['type'] = 'text';
		if ( $layout['type'] === 'image' ) {
			$this->render_image( $data );
		} else {
			$this->render_text( $data );
		}
	}

	/**
	 *  Render a checkbox field
	 *
	 * @since 20150323
	 * @param array $data field information
	 */
	private function render_checkbox( $data ) {
		extract( $data );  //  Keys are 'ID', 'value', 'layout', 'name'
		$attrs = array(
			'type' => 'checkbox',
			'id'   => $ID,
			'name' => $name,
			'value' => 'yes',
			'onchange' => ( array_key_exists( 'change', $layout ) ) ? $layout['change'] : '',
		);
		$this->checked( $attrs, $value, 'yes' );
		$html  = $this->get_tag( 'input', $attrs );
		$html .= '&nbsp;';
		$html .= $this->get_element( 'span', [ ], $layout['text'] );
		$this->element( 'label', [ ], $html, true );
	}

	/**
	 *  Render a multiple checkbox field
	 *
	 * @since 20170202
	 * @param array $data field information
	 */
	private function render_checkbox_multiple( $data ) {
		extract( $data );  //  Keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) {
			return;
		}
		if ( ! empty( $layout['text'] ) ) {
			$this->element( 'div', [ ], $layout['text'] );
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
			$html .= $this->get_element( 'span',  [ ], $text );
			$label = $this->get_element( 'label', [ ], $html, true );
			$this->element( 'div', [ ], $label, true );
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
		$text = ( ! empty( $layout['text'] ) ) ? $layout['text'] : '';
		if ( ! empty( $text ) ) {
			?>&nbsp;<?php
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
		if ( array_key_exists( 'default', $layout ) && ! empty( $value ) ) {
			e_esc_html( $value );
		}
		if ( ! empty( $layout['text'] ) ) {
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
		if ( ! empty( $data['layout']['text'] ) ) {
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
		$img_css = 'form-image-container' . ( ( empty( $value ) ) ? ' hidden' : '');
		$btn_css = 'form-image-delete' . ( ( empty( $value ) ) ? ' hidden' : '');
		$attrs = array(
			'id'    => $ID . '_input',
			'type'  => 'text',
			'class' => 'hidden',
			'name'  => $name,
			'value' => $value,
		);
		$html  = $this->get_element( 'button', [ 'type' => "button", 'class' => "form-image" ], $media['button'] );
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
		if ( empty( $layout['source'] ) ) return;
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
			$html .= $this->get_element( 'div', [ ], $layout['postext'] ) ;
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
		if ( empty( $layout['source'] ) )
			return;
		$pre_css   = ( array_key_exists( 'textcss', $layout ) ) ? $layout['textcss'] : '';
		$pre_text  = ( array_key_exists( 'text',    $layout ) ) ? $layout['text']    : '';
		$post_text = ( array_key_exists( 'postext', $layout ) ) ? $layout['postext'] : '';
		$preset    = ( array_key_exists( 'preset',  $layout ) ) ? $layout['preset']  : 'no';
		//  Pre-Text
		$html = $this->get_element( 'div', [ 'class' => $pre_css ], $pre_text );
		//  Radio labels
		$label  = $this->get_element( 'span', [ 'class' => 'radio-multiple-yes' ],    __( 'Yes', 'privacy-my-way' ) );
		$label .= '&nbsp;';
		$label .= $this->get_element( 'span', [ 'class' => 'radio-multiple-no'  ],    __( 'No',  'privacy-my-way' ) );
		$html  .= $this->get_element( 'div',  [ 'class' => 'radio-multiple-header' ], $label, true );
		//  Radio buttons
		foreach( $layout['source'] as $key => $text ) {
			$check  = ( array_key_exists( $key, $value ) ) ? $value[ $key ] : $preset;
			//  Yes radio
			$yes = array(
				'type'  => 'radio',
				'value' => 'yes',
				'class' => 'radio-multiple-list radio-multiple-list-yes',
				'name'  => $name .'[' . $key . ']',
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
	 *  Render a select field
	 *
	 * @since 20150323
	 * @param array $data field information
	 */
	private function render_select( $data ) {
		extract( $data );  //  Extracts 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) {
			return;
		}
		if ( ! empty( $layout['text'] ) ) {
			$this->element( 'div', [ 'class' => 'form-select-text' ], $layout['text'] );
		}
		$attrs = array(
			'id'   => $ID,
			'name' => $name
		);
		if ( strpos( '[]', $name ) ) {
			$attrs['multiple'] = 'multiple';
		}
		if ( array_key_exists( 'change', $layout ) ) {
			$attrs['onchange'] = $layout['change'];
		}
		$this->tag( 'select', $attrs );
			$source_func = $layout['source'];
			if ( is_array( $source_func ) ) {
				foreach( $source_func as $key => $text ) {
					$attrs = [ 'value' => $key ];
					$this->selected( $attrs, $key, $value );
					$this->element( 'option', $attrs, ' ' . $text . ' ' );
				}
			} else if ( method_exists( $this, $source_func ) ) {
				$this->$source_func( $value );
			} else if ( function_exists( $source_func ) ) {
				$source_func( $value );
			} ?>
		</select><?php
	}

	/**
	 *  Render a field with multiple selects
	 *
	 * @since 20170228
	 * @param array $data field information
	 */
	private function render_select_multiple( $data ) {
		$data['name'] .= '[]';
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
		$attrs = array(
			'type'  => 'number',
			'class' => 'small-text',
			'id'    => $ID,
			'name'  => $name,
			'min'   => '1',
			'step'  => '1',
			'value' => $value,
		);
		$this->element( 'input', $attrs );
		if ( ! empty( $layout['stext'] ) ) e_esc_attr( $layout['stext'] );
	}

	/**
	 *  Render text on the form
	 *
	 * @since 20150323
	 * @param array $data  Field information.
	 */
	private function render_text( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		if ( ! empty( $layout['text'] ) ) {
			$this->element( 'p', [ ], ' ' . $layout['text'] );
		}
		$attrs = array(
			'type'  => 'text',
			'id'    => $ID,
			'class' => ( array_key_exists( 'class', $layout ) )  ? $layout['class'] : 'regular-text',
			'name'  => $name,
			'value' => $value,
			'title' => ( array_key_exists( 'help', $layout ) )   ? $layout['help']  : '',
			'placeholder' => ( array_key_exists( 'place',  $layout ) ) ? $layout['place'] : '',
			'onchange'    => ( array_key_exists( 'change', $layout ) ) ? $layout['change']  : '',
		);
		$this->element( 'input', $attrs );
		if ( ! empty( $layout['stext'] ) ) {
			e_esc_html( ' ' . $layout['stext'] );
		}
		if ( ! empty( $layout['etext'] ) ) {
			$this->element( 'p', [ ], ' ' . $layout['etext'] );
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
			return $output;
		}
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
			return $output;
		}
		foreach( $input as $key => $data ) {
			$item = ( array_key_exists( $key, $this->form[ $option ]['layout'] ) ) ? $this->form[ $option ]['layout'][ $key ] : array();
			if ( (array)$data === $data ) {
				foreach( $data as $ID => $subdata ) {
					$output[ $key ][ $ID ] = $this->do_validate_function( $subdata, $item );
				}
			} else {
				$output[ $key ] = $this->do_validate_function( $data, $item );
			}
		}
		return apply_filters( $this->current . '_validate_settings', $output, $input );
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
			$output = $this->$func( $input );
		} else if ( function_exists( $func ) ) {
			$output = $func( $input );
		} else { // FIXME:  test for data type?
			$output = $this->validate_text( $input );
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
	private function validate_checkbox( $input ) {
		return sanitize_key( $input );
	}

	/**
	 *  Validate multiple checkbox field
	 *
	 * @since 20180307
	 * @param string $input
	 * @return string
	 */
	private function validate_checkbox_multiple( $input ) {
		return sanitize_key( $input );
	}

	/**
	 *  Validate colorpicker field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_colorpicker( $input ) {
		return ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $input ) ) ? $input : '';
	}

	/**
	 *  Validate font field value
	 *
	 * @since 20170228
	 * @param string $input
	 * @return string
	 */
	private function validate_font( $input ) {
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
	private function validate_image( $input ) {
		return apply_filters( 'pre_link_image', $input );
	}

	/**
	 *  Validate content field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_post_content( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 *  Validate radio field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_radio( $input ) {
		return sanitize_key( $input );
	}

	/**
	 *  Validate multiple radio fields
	 *
	 * @since 20170228
	 * @param string $input
	 * @return string
	 */
	private function validate_radio_multiple( $input ) {
		return $this->validate_radio( $input );
	}

	/**
	 *  Validate select field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_select( $input ) {
		return sanitize_file_name( $input );
	}

	/**
	 *  Validate select field with multiple values
	 *
	 * @since 20170228
	 * @param string $input
	 * @return string
	 */
	private function validate_select_multiple( $input ) {
		return array_map( array( $this, 'validate_select' ), $input ); // FIXME
	}

	/**
	 *  Validate spinner field value
	 *
	 * @since 20170305
	 * @param string $input
	 * @return string
	 */
	private function validate_spinner( $input ) {
		return $this->validate_text( $input );
	}

	/**
	 *  Validate text field value
	 *
	 * @since 20170305
	 * @param string $input
	 * @return string
	 */
	protected function validate_text( $input ) {
		return wp_kses_data( $input );
	}

	/**
	 *  Validate text color field value
	 *
	 * @since 20160910
	 * @param string $input
	 * @return string
	 */
	private function validate_text_color( $input ) {
		return $this->validate_text( $input );
	}

	/**
	 *  Validate url field value
	 *
	 * @since 20150323
	 * @param string $input
	 * @return string
	 */
	private function validate_url( $input ) {
		return apply_filters( 'pre_link_url', $input );
	}


} # end of PMW_Form_Admin class


/**  These are just shorthand functions  **/

/**
 *  Echo an escaped attribute string
 *
 * @param string $string
 * @see esc_attr()
 */
if ( ! function_exists( 'e_esc_attr' ) ) {
	function e_esc_attr( $string ) {
		echo esc_attr( $string );
	}
}

/**
 *  Echo an escaped HTML string
 *
 * @param string $string
 * @see esc_html()
 */
if ( ! function_exists( 'e_esc_html' ) ) {
	function e_esc_html( $string ) {
		echo esc_html( $string );
	}
}

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
