<?php
/**
 *  Display admin forms
 *
 * @package Privacy_My_Way
 * @subpackage Admin
 * @since 20150323
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Form/Admin.php
 */
defined( 'ABSPATH' ) || exit;
/**
 *  Abstract class to provide basic functionality for displaying admin option screens
 */
abstract class PMW_Form_Admin {

	/**
	 * @since 20150926
	 * @var string name of screen options saved in WP dbf
	 */
	protected $current   = '';
	/**
	 * @since 20150323
	 * @var array controls screen layout and display
	 */
	protected $form      =  array();
	/**
	 * @since 20150926
	 * @var array screen options array from WP dbf
	 */
	protected $form_opts =  array();
	/**
	 * @since 20150323
	 * @var array contains translated text strings
	 */
	protected $form_text =  array();
	/**
	 *  This is the admin menu hook, and should be set in the child class.
	 *
	 * @since 20160212
	 * @var string
	 */
	protected $hook_suffix;
	/**
	 * @since 20150323
	 * @var string callback function for settings field
	 */
	protected $options;
	/**
	 * @since 20150323
	 * @var string screen options name prefix
	 */
	protected $prefix    = 'tcc_options_';
	/**
	 * @since 20150323
	 * @var string name of function that registers the form
	 */
	protected $register;
	/**
	 * @since 20150323
	 * @var string callback function for rendering fields
	 */
	protected $render;
	/**
	 * @since 20150323
	 * @var string page slug
	 */
	protected $slug      = 'default_page_slug';
	/**
	 * @since 20151001
	 * @var string form tab to be shown to user
	 */
	public    $tab       = 'about';
	/**
	 * @since 20150323
	 * @var string form type: 'single','tabbed'
	 * @todo add 'multi'
	 */
	protected $type      = 'single';
	/**
	 * @since 20150323
	 * @var string callback function for field validation
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
	 *  Abstract function declaration for child classes.  Function should return an array.
	 *
	 * @since 20150323
	 * @used-by PMW_Form_Admin::load_form_page()
	 */
	abstract protected function form_layout( $option );
	/**
	 *  Default function to provide text at top of form screen
	 *
	 * @since 20150323
	 */
	public function description() { return ''; }

	/**
	 *  Constructor function
	 *
	 * @since 20150323
	 * @uses PMW_Form_Admin::screen_type()
	 * @see add_action()
	 */
	protected function __construct() {
		$this->screen_type();
		add_action( 'admin_init', [ $this, 'load_form_page' ] );
	}

	/**
	 *  Handles setup for loading the form.  Provides a do_action call for 'tcc_load_form_page'.
	 *
	 * @since 20150926
	 * @see wp_get_referer()
	 * @see sanitize_key()
	 * @see get_transient()
	 * @see set_transient()
	 * @uses PMW_Form_Admin::form_text()
	 * @uses PMW_Form_Admin::form_layout()
	 * @uses PMW_Form_Admin::determine_option()
	 * @uses PMW_Form_Admin::get_form_options()
	 * @see do_action()
	 * @see add_action()
	 */
	public function load_form_page() {
		global $plugin_page;
		if ( ( $plugin_page === $this->slug ) || ( ( $refer = wp_get_referer() ) && ( strpos( $refer, $this->slug ) ) ) ) {
			if ( $this->type === 'tabbed' ) {
				if ( isset( $_POST['tab'] ) ) {
					$this->tab = sanitize_key( $_POST['tab'] );
				} else if ( isset( $_GET['tab'] ) )  {
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
			if ( ( $this->type === 'tabbed' ) && ! isset( $this->form[ $this->tab ] ) ) {
				$this->tab = 'about';
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
	 * @param string $hook_suffix admin page menu option suffix - passed by WP but not used
	 * @see wp_enqueue_media()
	 * @see wp_enqueue_style()
	 * @see get_theme_file_uri()
	 * @see wp_enqueue_script()
	 * @see wp_localize_script()
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		wp_enqueue_media();
		wp_enqueue_style(  'admin-form.css', get_theme_file_uri( 'css/admin-form.css' ), [ 'wp-color-picker' ] );
		wp_enqueue_script( 'admin-form.js',  get_theme_file_uri( 'js/admin-form.js' ),   [ 'jquery', 'wp-color-picker' ], false, true );
		$options = apply_filters( 'tcc_form_admin_options_localization', array() );
		if ( $options ) {
			$options = $this->normalize_options( $options, $options );
			wp_localize_script( 'admin-form.js', 'tcc_admin_options', $options );
		}
	}

	/**
	 *  Ensures 'showhide' sub-arrays contain all required subscripts for the javascript.
	 *
	 * @since 20170505
	 * @param array $new
	 * @param array $old
	 * @return array
	 */
	protected function normalize_options( $new, $old ) {
		if ( isset( $old['showhide'] ) ) {
			$new['showhide'] = array_map( [ $this, 'normalize_showhide' ], $old['showhide'] );
		}
		return $new;
	}

	/**
	 *  Provides array defaults
	 *
	 * @since 20170507
	 * @param array $item
	 * @return array
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
	 * @see _x()
	 * @see apply_filters()
	 * @used-by PMW_Form_Admin::load_form_page()
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
	 * @used-by PMW_Form_Admin::__constructor()
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
	 * @see register_setting()
	 * @see add_settings_section()
	 */
	public function register_single_form() {
		register_setting( $this->current, $this->current, [ $this, $this->validate ] );
		$title = ( isset( $this->form['title']    ) ) ? $this->form['title']    : '';
		$desc  = ( isset( $this->form['describe'] ) ) ? $this->form['describe'] : 'description';
		$desc  = ( is_array( $desc ) ) ? $desc : ( ( method_exists( $this, $desc ) ) ? [ $this, $desc ] : $desc );
		add_settings_section( $this->current, $title, $desc, $this->current );
		foreach( $this->form['layout'] as $item => $data ) {
			if ( is_string( $data ) ) {
				continue;	#	skip string variables
			}
			$this->register_field( $this->current, $this->current, $item, $data );
		}
	}

	/**
	 *  Setup for tabbed form fields
	 *
	 * @since 20150323
	 * @see register_setting()
	 * @see add_settings_section()
	 */
	public function register_tabbed_form() {
		$validater = ( isset( $this->form['validate'] ) ) ? $this->form['validate'] : $this->validate;
		foreach( $this->form as $key => $section ) {
			if ( ! ( (array)$section === $section ) )
				continue; // skip string variables
			if ( ! ( $section['option'] === $this->current ) )
				continue; // skip all but current screen
			$validate = ( isset( $section['validate'] ) ) ? $section['validate'] : $validater;
			$current  = ( isset( $this->form[ $key ]['option'] ) ) ? $this->form[ $key ]['option'] : $this->prefix . $key;
			register_setting( $current, $current, [ $this, $validate ] );
			$title    = ( isset( $section['title'] ) )    ? $section['title']    : '';
			$describe = ( isset( $section['describe'] ) ) ? $section['describe'] : 'description';
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
	 * @see add_settings_field()
	 */
	private function register_field( $option, $key, $itemID, $data ) {
		if ( is_string( $data ) )
			return; // skip string variables
		if ( ! isset( $data['render'] ) )
			return; // skip variables without render data
		if ( $data['render'] === 'skip' )
			return; // skip variable when needed
		if ( $data['render'] === 'array' ) { /*
			$count = max( count( $data['default'] ), count( $this->form_opts[ $key ][ $itemID ] ) );
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
	 * @param string $ID field ID
	 * @param array $data field data
	 * @uses PMW_Trait_Attributes::get_element()
	 * @return string
	 */
	private function field_label( $ID, $data ) {
		$data  = array_merge( [ 'help' => '', 'label' => '' ], $data );
		$attrs = array(
			'title' => $data['help'],
		);
		if ( in_array( $data['render'], [ 'display', 'radio_multiple' ] ) ) {
			return $this->get_element( 'span', $attrs, $data['label'] );
		} else if ( $data['render'] === 'title' ) {
			$attrs['class'] = 'form-title';
			return $this->get_element( 'span', $attrs, $data['label'] );
		} else {
			$attrs['for'] = $ID;
			return $this->get_element( 'label', $attrs, $data['label'] );
		}
		return '';
	}

	/**
	 *  Checks to make sure that field's validation callback function is callable
	 *
	 * @since 20160228
	 * @param array $data field data
	 * @return string
	 */
	private function sanitize_callback( $data ) {
		$valid_func = "validate_{$data['render']}";
		if ( is_array( $valid_func ) && method_exists( $valid_func[0], $valid_func[1] ) ) {
			$callback = $valid_func;
		} else if ( method_exists( $this, $valid_func ) ) {
			$callback = [ $this, $valid_func ];
		} else if ( function_exists( $valid_func ) ) {
			$callback = $valid_func;
		} else {
			$callback = 'wp_kses_post';
		}
		return $callback;
	}


  /**  Data functions  **/

	/**
	 *  Determine 'current' property value
	 *
	 * @since 20150323
	 * @used-by PMW_Form_Admin::load_form_page()
	 */
	private function determine_option() {
		if ( $this->type === 'single' ) {
			$this->current = $this->prefix . $this->slug;
		} else if ( $this->type === 'tabbed' ) {
			if ( isset( $this->form[ $this->tab ]['option'] ) ) {
				$this->current = $this->form[ $this->tab ]['option'];
			} else {
				$this->current = $this->prefix . $this->tab;
			}
		}
	}

	/**
	 *  Retrieve form fields default values.
	 *
	 * @since 20150323
	 * @param string $option tabbed page option
	 * @uses PMW_Trait_Logging::logg()
	 * @return array
	 */
	protected function get_defaults( $option = '' ) {
		if ( empty( $this->form ) ) {
			$this->form = $this->form_layout();
		}
		$defaults = array();
		if ( $this->type === 'single' ) {
			foreach( $this->form['layout'] as $ID => $item ) {
				if ( is_string( $item ) || empty( $item['default'] ) ) {
					continue;
				}
				$defaults[ $ID ] = $item['default'];
			}
		} else {  //  tabbed page
			if ( isset( $this->form[ $option ] ) ) {
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
	 * @see get_option()
	 * @used-by PMW_Form_Admin::load_form_page()
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
	 * @see settings_errors()
	 * @see do_action()
	 * @see settings_fields()
	 * @see do_settings_section()
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
	 * @see sanitize_key()
	 * @uses e_esc_html()
	 * @see settings_errors()
	 * @uses e_esc_attr()
	 * @see do_action()
	 * @see settings_fields()
	 * @see do_settings_section()
	 */
	public function render_tabbed_form() {
		$active_page = sanitize_key( $_GET['page'] ); ?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"></div>
			<h1 class='centered'><?php
				e_esc_html( $this->form['title'] ); ?>
			</h1><?php
			settings_errors(); ?>
			<h2 class="nav-tab-wrapper"><?php
				$refer = "admin.php?page=$active_page";
				foreach( $this->form as $key => $menu_item ) {
					if ( is_string( $menu_item ) ) continue;
					$tab_ref  = "$refer&tab=$key";
					$tab_css  = 'nav-tab' . ( ( $this->tab === $key ) ? ' nav-tab-active' : '' ); ?>
					<a href='<?php e_esc_attr( $tab_ref ); ?>' class='<?php e_esc_attr( $tab_css ); ?>'><?php
						if ( ! empty( $menu_item['icon'] ) ) { ?>
							<i class="dashicons <?php e_esc_attr( $menu_item['icon'] ); ?>"></i><?php
						}
						e_esc_html( $menu_item['title'] ); ?>
					</a><?php
				} ?>
			</h2>
			<form method="post" action="options.php">
				<input type='hidden' name='tab' value='<?php e_esc_attr( $this->tab ); ?>'><?php
				$current = ( isset( $this->form[ $this->tab ]['option'] ) ) ? $this->form[ $this->tab ]['option'] : $this->prefix . $this->tab;
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
	 * @param string $title reset button text
	 * @see submit_button()
	 */
	private function submit_buttons( $title = '' ) {
		if ( ! isset( $this->form_text['submit'] ) ) { pmw()->log( 'stack' ); $this->form_text(); } // track down erratic bug
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
	 * @uses PMW_Trait_Attributes::tag()
	 * @uses e_esc_html()
	 * @uses PMW_Trait_Logging::logg()
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
				$value = ( isset( $data[ $item ] ) ) ? $data[ $item ] : '';
				if ( $layout[ $item ]['render'] === 'array' ) {
					$name .= '[' . $num . ']';
					#if ( isset( $add ) && $add ) { $layout[ $item ]['add'] = true; }
					$value = ( isset( $data[ $item ][ $num ] ) ) ? $data[ $item ][ $num ] : '';
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
	 * @param array $args field identificatin information
	 * @uses PMW_Trait_Attributes::tag()
	 * @uses e_esc_html()
	 * @uses PMW_Trait_Logging::log()
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
			if ( ! isset( $data[ $item ] ) ) {
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
	 *  Render fields on multiple screen form
	 *
	 * @since 20150323
	 * @param array $args
	 */
	public function render_multi_options( $args ) {
	}

	/**
	 *  Determine field attributes
	 *
	 * @since 20161206
	 * @param array $layout field characteristics
	 * @return array
	 */
	private function render_attributes( $layout ) {
		$attrs = array();
		$attrs['class'] = ( ! empty( $layout['divcss'] ) ) ? $layout['divcss'] : '';
		$attrs['title'] = ( isset( $layout['help'] ) )     ? $layout['help']   : '';
		if ( ! empty( $layout['showhide'] ) ) {
			$state = array_merge( [ 'show' => null, 'hide' => null ], $layout['showhide'] );
			$attrs['data-item'] = ( isset( $state['item'] ) ) ? $state['item'] : $state['target'];
			$attrs['data-show'] = $state['show'];
			$attrs['data-hide'] = $state['hide'];
		}
		return $attrs;
	}


	/*  Render Items functions
	 *
	 *
	 *  $data = array('ID'=>$field, 'value'=>$value, 'layout'=>$layout[$item], 'name'=>$name);
	 *
	 **/

	/**
	 *  Render an array
	 *
	 * @since 20150927
	 * @param array $data field information
	 * @todo needs add/delete/sort
	 */
	private function render_array( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		if ( ! isset( $layout['type'] ) ) { $layout['type'] = 'text'; }
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
	 * @uses PMW_Trait_Attributes::checked()
	 * @uses PMW_Trait_Attributes::tag()
	 * @uses e_esc_html()
	 */
	private function render_checkbox( $data ) {
		extract( $data );  #  associative array: keys are 'ID', 'value', 'layout', 'name'
		$attrs = array(
			'type' => 'checkbox',
			'id'   => $ID,
			'name' => $name,
			'value' => 'yes',
			'onchange' => ( isset( $layout['change'] ) ) ? $layout['change'] : '',
		);
		$this->checked( $attrs, $value, 'yes' ); ?>
		<label>
			<?php $this->tag( 'input', $attrs ); ?>&nbsp;
			<span>
				<?php e_esc_html( $layout['text'] ); ?>
			</span>
		</label><?php
	}

	/**
	 *  Render a multiple checkbox field
	 *
	 * @since 20170202
	 * @param array $data field information
	 * @uses e_esc_html()
	 * @uses PMW_Trait_Attributes::checked()
	 * @uses PMW_Trait_Attributes::tag()
	 */
	private function render_checkbox_multiple( $data ) {
		extract( $data );  #  associative array: keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) {
			return;
		}
		if ( ! empty( $layout['text'] ) ) { ?>
			<div>
				<?php e_esc_html( $layout['text'] ); ?>
			</div><?php
		}
		foreach( $layout['source'] as $key => $text ) {
			$attrs = array(
				'type'  => 'checkbox',
				'id'    => $ID . '-' . $key,
				'name'  => $name . '[' . $key . ']',
				'value' => $key,
			);
			$check = isset( $value[ $key ] ) ? true : false;
			$this->checked( $attrs, $check ); ?>
			<div>
				<label>
					<?php $this->tag( 'input', $attrs ); ?>&nbsp;
					<span>
						<?php e_esc_html( $text ); ?>
					</span>
				</label>
			</div><?php
		}
	}

	/**
	 *  Render colorpicker field
	 *
	 * @since 20150927
	 * @param array $data field information
	 * @uses PMW_Trait_Attributes::element()
	 * @uses e_esc_html()
	 */
	private function render_colorpicker($data) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
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
			e_esc_html( '&nbsp;' );
			$this->element( 'span', [ 'class' => 'form-colorpicker-text' ], $text );
		}
	}

	/**
	 *  Display a field as text
	 *
	 * @since 20160201
	 * @uses e_esc_html()
	 * @uses PMW_Trait_Attributes::element()
	 */
	private function render_display( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		if ( isset( $layout['default'] ) && ! empty( $value ) ) {
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
	 * @uses PMW_Trait_Attributes::tag()
	 * @uses PMW_Trait_Attributes::selected()
	 * @uses PMW_Trait_Attributes::element()
	 */
	private function render_font( $data ) {
		extract( $data );  #  array('ID'=>$item, 'value'=>$data[$item], 'layout'=>$layout[$item], 'name'=>$name)
		$attrs = array(
			'id'       => $ID,
			'name'     => "{$name}[]",
			'multiple' => ''
		);
		if ( isset( $layout['change'] ) ) {
			$attrs['onchange'] = $layout['change'];
		}
		$this->tag( 'select', $attrs );
			foreach( $layout['source'] as $key => $text ) {
				$attrs = [ 'value' => $key ];
				$this->selected( $attrs, $key, $value );
				$this->element( 'option', $attrs, ' ' . $key . ' ' );
			} ?>
		</select><?php
		if ( ! empty( $data['layout']['text'] ) ) {
			$this->element( 'span', [ ], ' ' . $data['layout']['text'] );
		}
	}

	/**
	 *  Render an image on the form
	 *
	 * @since 20150925
	 * @param array $data
	 * @uses e_esc_attr()
	 * @uses e_esc_html()
	 * @todo make use of Attributes Trait
	 */
	private function render_image( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		$media   = $this->form_text['media'];
		$img_css = 'form-image-container' . ( ( empty( $value ) ) ? ' hidden' : '');
		$btn_css = 'form-image-delete' . ( ( empty( $value ) ) ? ' hidden' : '');
		if ( isset( $layout['media'] ) ) { $media = array_merge( $media, $layout['media'] ); } ?>
		<div data-title="<?php e_esc_attr( $media['title'] ); ?>"
			  data-button="<?php e_esc_attr( $media['button'] ); ?>" data-field="<?php e_esc_attr( $ID ); ?>">
			<button type="button" class="form-image">
				<?php e_esc_html( $media['button'] ); ?>
			</button>
			<input id="<?php e_esc_attr( $ID ); ?>_input" type="text" class="hidden" name="<?php e_esc_attr( $name ); ?>" value="<?php e_esc_html( $value ); ?>" />
			<div class="<?php e_esc_attr( $img_css ); ?>">
				<img id="<?php e_esc_attr( $ID ); ?>_img" src="<?php e_esc_attr( $value ); ?>" alt="<?php e_esc_attr( $value ); ?>">
			</div>
			<button type="button" class="<?php e_esc_attr( $btn_css ); ?>">
				<?php e_esc_html( $media['delete'] ); ?>
			</button>
		</div><?php
	}

	/**
	 *  Render radio field
	 *
	 * @since 20160201
	 * @uses PMW_Trait_Attributes::element()
	 * @uses PMW_Trait_Attributes::checked()
	 * @uses PMW_Trait_Attributes::tag()
	 * @see wp_kses()
	 * @uses PMW_Theme_Library::kses()
	 * @uses e_esc_html()
	 */
	private function render_radio($data) {
		extract( $data );  #  associative array: keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) ) return;
		$radio_attrs = array(
			'type'     => 'radio',
			'name'     => $name,
			'onchange' => ( isset( $layout['change'] ) ) ? $layout['change'] : '',
		); ?>
		<div><?php
			if ( isset( $layout['text'] ) ) {
				$uniq = uniqid();
				$this->element( 'div', [ 'id' => $uniq ], $layout['text'] );
				$radio_attrs['aria-describedby'] = $uniq;
			}
			foreach( $layout['source'] as $key => $text ) {
				$radio_attrs['value'] = $key;
				$this->checked( $radio_attrs, $value, $key ); ?>
				<div>
					<label><?php
						$this->tag( 'input', $attrs );
						if ( isset( $layout['src-html'] ) ) {
							echo wp_kses( $text, pmw()->kses() );
						} else {
							e_esc_html( $text );
						}
						if ( isset( $layout['extra_html'][ $key ] ) ) {
							echo wp_kses( $layout['extra_html'][ $key ], pmw()->kses() );
						} ?>
					</label>
				</div><?php
			}
			if ( isset( $layout['postext'] ) ) { ?>
				<div>
					<?php e_esc_html( $layout['postext'] ) ; ?>
				</div><?php
			} ?>
		</div><?php
	}

	/**
	 *  Render multiple radio fields - this has limited use - only displays yes/no radios
	 *
	 * @since 20170202
	 * @param array $data field information
	 * @uses PMW_Trait_Attributes::element()
	 * @see esc_html_e()
	 * @see esc_attr()
	 * @see checked()
	 * @see wp_kses()
	 * @uses PMW_Theme_Library::kses()
	 * @uses e_esc_html()
	 */
	private function render_radio_multiple( $data ) {
		extract( $data );   #   associative array: keys are 'ID', 'value', 'layout', 'name'
		if ( empty( $layout['source'] ) )
			return;
		$pre_css   = ( isset( $layout['textcss'] ) ) ? $layout['textcss'] : '';
		$pre_text  = ( isset( $layout['text'] ) )    ? $layout['text']    : '';
		$post_text = ( isset( $layout['postext'] ) ) ? $layout['postext'] : '';
		$preset    = ( isset( $layout['preset'] ) )  ? $layout['preset']  : 'no'; ?>
		<div class="radio-multiple-div">
			<?php $this->element( 'div', [ 'class' => $pre_css ], $pre_text ); ?>
			<div class="radio-multiple-header">
				<span class="radio-multiple-yes"><?php esc_html_e( 'Yes', 'privacy-my-way' ); ?></span>&nbsp;
				<span class="radio-multiple-no" ><?php esc_html_e( 'No', 'privacy-my-way' ); ?></span>
			</div><?php
			foreach( $layout['source'] as $key => $text ) {
				$check  = ( isset( $value[ $key ] ) ) ? $value[ $key ] : $preset; ?>
				<div class="radio-multiple-list-item">
					<label>
						<input type="radio" value="yes" class="radio-multiple-list radio-multiple-list-yes"
						       name="<?php echo esc_attr( $name.'['.$key.']' ) ; ?>"
						       <?php checked( $check, 'yes' ); ?> />&nbsp;
						<input type="radio" value="no" class="radio-multiple-list radio-multiple-list-no"
						       name="<?php echo esc_attr( $name.'['.$key.']' ) ; ?>"
						       <?php checked( $check, 'no' ); ?> />
						<span class="radio-multiple-list-text">
							<?php echo wp_kses( $text, pmw()->kses() ); ?>
						</span>
					</label>
				</div><?php
			} ?>
			<div class="radio-multiple-post-text">
				<?php e_esc_html( $post_text ) ; ?>
			</div>
		</div><?php
	}

	/**
	 *  Render a select field
	 *
	 * @since 20150323
	 * @param array $data
	 * @uses PMW_Trait_Attributes::element()
	 * @uses PMW_Trait_Attributes::tag()
	 * @uses PMW_Trait_Attributes::selected()
	 */
	private function render_select( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
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
		if ( ! ( strpos( '[]', $name ) === false ) ) {
			$attrs['multiple'] = 'multiple';
		}
		if ( isset( $layout['change'] ) ) {
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
			} elseif ( method_exists( $this, $source_func ) ) {
				$this->$source_func( $value );
			} elseif ( function_exists( $source_func ) ) {
				$source_func( $value );
			} ?>
		</select><?php
	}

	/**
	 *  Render a field with multiple selects
	 *
	 * @since 20170228
	 * @see array $data field information
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
	 * @uses PMW_Trait_Attributes::element()
	 * @uses e_esc_html()
	 */
	private function render_spinner( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
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
		if ( ! empty( $layout['stext'] ) ) { e_esc_attr( $layout['stext'] ); }
	}

	/**
	 *  Render text on the form
	 *
	 * @since 20150323
	 * @param array $data field information
	 * @uses PMW_Trait_Attributes::element()
	 * @uses e_esc_html()
	 */
	private function render_text( $data ) {
		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		if ( ! empty( $layout['text'] ) ) {
			$this->element( 'p', [ ], ' ' . $layout['text'] );
		}
		$attrs = array(
			'type'  => 'text',
			'id'    => $ID,
			'class' => ( isset( $layout['class'] ) )  ? $layout['class'] : 'regular-text',
			'name'  => $name,
			'value' => $value,
			'title' => ( isset( $layout['help'] ) )   ? $layout['help']  : '',
			'placeholder' => ( isset( $layout['place'] ) ) ? $layout['place'] : '',
			'onchange'    => ( isset( $layout['change'] ) ) ? $layout['change']  : '',
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
		$data['value'] = ( isset( $this->form_opts[ $index ] ) ) ? $this->form_opts[ $index ] : $data['layout']['color'];
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
/*		extract( $data );  #  array( 'ID' => $item, 'value' => $data[ $item ], 'layout' => $layout[ $item ], 'name' => $name )
		if ( ! empty( $layout['text'] ) ) {
			$data['layout']['text'] = "<b>{$layout['text']}</b>";
		} */
		$this->render_display( $data );
	}

	/**  Validate functions  **/

	/**
	 *  Handles validation for single form fields
	 *
	 * @since 20150323
	 * @param array $input input field list
	 * @see add_settings_error()
	 * @see apply_filters()
	 * @return array validated fields
	 * @todo notify user of missing required fields
	 */
	public function validate_single_form( $input ) {
		$output = $this->get_defaults();
		if ( isset( $_POST['reset'] ) ) {
			$object = ( isset( $this->form['title'] ) ) ? $this->form['title'] : $this->form_test['submit']['object'];
			$string = sprintf( $this->form_text['submit']['restore'], $object );
			add_settings_error( $this->slug, 'restore_defaults', $string, 'updated fade' );
			return $output;
		}
		foreach( $input as $ID => $data ) {
			$item = $this->form['layout'][ $ID ];
			$multiple = array( 'array', 'radio_multiple' );
			if ( in_array( $item['render'], $multiple ) ) {
				$item['render'] = ( isset( $item['type'] ) ) ? $item['type'] : 'text';
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
			if ( is_array( $item ) && isset( $item['require'] ) ) {
				if ( empty( $output[ $ID ] ) ) {
					$output[ $ID ] = $item['default'];
				}
			}
		}
		return apply_filters( "{$this->slug}_validate_settings", $output, $input );
	}

	/**
	 *  Handles validation for tabbed form fields
	 *
	 * @since 20150927
	 * @param array $input incoming field values
	 * @see sanitize_key()
	 * @see add_settings_error()
	 * @see apply_filters()
	 * @return array validated field values
	 */
	public function validate_tabbed_form( $input ) {
		$option = sanitize_key( $_POST['tab'] );
		$output = $this->get_defaults( $option );
		if ( isset( $_POST['reset'] ) ) {
			$object = ( isset( $this->form[ $option ]['title'] ) ) ? $this->form[ $option ]['title'] : $this->form_test['submit']['object'];
			$string = sprintf( $this->form_text['submit']['restore'], $object );
			add_settings_error( $this->slug, 'restore_defaults', $string, 'updated fade' );
			return $output;
		}
		foreach( $input as $key => $data ) {
			$item = ( isset( $this->form[ $option ]['layout'][ $key ] ) ) ? $this->form[ $option ]['layout'][ $key ] : array();
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
	 * @uses PMW_Trait_Logging::logg()
	 */
	private function do_validate_function( $input, $item ) {
		if ( empty( $item['render'] ) ) {
			$item['render'] = 'non_existing_render_type';
		}
		$func = ( isset( $item['validate'] ) ) ? $item['validate'] : 'validate_' . $item['render'];
		if ( method_exists( $this, $func ) ) {
			$output = $this->$func( $input );
		} elseif ( function_exists( $func ) ) {
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

#   These are just a shorthand functions

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
