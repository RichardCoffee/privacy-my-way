<?php
/**
 *  Display admin option forms
 *
 * @package Privacy_My_Way
 * @subpackage Forms
 * @since 20170505
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Options/Options.php
 */
defined( 'ABSPATH' ) || exit;
/**
 *  Abstract class to provide basic functionality for controlling option screen layouts
 */
abstract class PMW_Options_Options {


	/**
	 * @since 20170505
	 * @var string slug name for option
	 */
	protected $base = 'options';
	/**
	 * @since 20180404
	 * @var string user capability required to edit the options form
	 */
	protected $capability = 'edit_theme_options';
	/**
	 * @since 20170505
	 * @var integer tab priority on multi-tabbed screens
	 */
	protected $priority = 1000;
	/**
	 * @since 20170505
	 * @var array contains screen layout
	 */
	protected $screen = array();

	/**
	 *  Function provides the name of the screen or tab
	 *
	 * @since 20170505
	 */
	abstract protected function form_title();
	/**
	 *  Function provides the name of the icon to be displayed
	 *
	 * @since 20180831
	 */
	abstract protected function form_icon();
	/**
	 *  Function provides a text description of the screen
	 *
	 * @since 20170505
	 */
	abstract public function describe_options();
	/**
	 *  Function provides the screen layout
	 *
	 * @since 20170505
	 */
	abstract protected function options_layout();


	/**
	 *  Constructor function
	 *
	 * @since 20170505
	 */
	public function __construct() {
		add_filter( 'fluidity_options_form_layout',        [ $this, 'form_layout' ],          $this->priority );
		add_filter( 'tcc_form_admin_options_localization', [ $this, 'options_localization' ], $this->priority );
	}

	/**
	 *  Add options layout to a tabbed screen
	 *
	 * @since 20170505
	 * @param array $form contains form information determining the screen layout
	 * @return array
	 */
	public function form_layout( $form ) {
		if ( ! array_key_exists( $this->base, $form ) ) {
			$form[ $this->base ] = $this->default_form_layout();
		}
		return $form;
	}

	/**
	 *  Create screen option layout
	 *
	 * @since 20170505
	 * @return array
	 */
	public function default_form_layout() {
		if ( empty( $this->screen ) ) {
			$this->screen = array(
				'describe' => [ $this, 'describe_options' ],
				'title'    => $this->form_title(),
				'icon'     => $this->form_icon(),
				'option'   => 'tcc_options_' . $this->base,
				'layout'   => $this->options_layout(),
			);
		}
		return $this->screen;
	}

	/**
	 *  Add localzation data into array passed to javascript
	 *
	 * @since 20170505
	 * @param array $data
	 * @return array
	 */
	public function options_localization( $data = array() ) {
		if ( ! array_key_exists( 'showhide', $data ) ) {
			$data['showhide'] = array();
		}
		$options = ( ! empty( $this->screen['layout'] ) ) ? $this->screen['layout'] : $this->options_layout();
		foreach( $options as $key => $item ) {
			if ( ! is_array( $item ) ) continue;
			if ( array_key_exists( 'showhide', $item ) ) {
				$data['showhide'][] = $item['showhide'];
			}
		}
		return $data;
	}

	/**
	 *  Create an array containing default option values
	 *
	 * @since 20170505
	 * @param array
	 */
	public function get_default_options() {
		$form = $this->options_layout( true );
		$opts = array();
		foreach( $form as $key => $option ) {
			if ( ! is_array( $option ) ) continue;
			if ( array_key_exists( 'default', $option ) ) {
				$opts[ $key ] = $option['default'];
			}
		}
		return $opts;
	}

	/**
	 *  Get the layout for a screen item
	 *
	 * @since 20180410
	 * @param string $item  slug of the item to be retrieved
	 * @return array
	 */
	public function get_item( $item ) {
		$layout = ( empty( $this->screen ) ) ? $this->options_layout() : $this->screen['layout'];
		return ( array_key_exists( $item, $layout ) ) ? $layout[ $item ] : array();
	}


}
