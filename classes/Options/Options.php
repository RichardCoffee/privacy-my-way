<?php
/**
 *  Display admin option forms - abstract class to provide basic functionality for controlling option screen layouts
 *
 * @package Privacy_My_Way
 * @subpackage Forms
 * @since 20170505
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Options/Options.php
 */
defined( 'ABSPATH' ) || exit;


abstract class PMW_Options_Options {


	/**
	 * @since 20170505
	 * @var string  Slug name for option.
	 */
	protected $base = 'options';
	/**
	 * @since 20180404
	 * @var string  User capability required to edit the options form.
	 */
	protected $capability = 'edit_theme_options';
	/**
	 * @since 20170505
	 * @var integer  Tab priority on multi-tabbed screens.
	 */
	protected $priority = 1000;
	/**
	 * @since 20170505
	 * @var array  Contains screen layout.
	 */
	protected $screen = array();


	/**
	 *  This function should return the title of the screen/tab
	 *
	 * @since 20170505
	 */
	abstract protected function form_title();
	/**
	 *  This function should return the slug of the dashicon to be displayed
	 *
	 * @since 20180831
	 */
	abstract protected function form_icon();
	/**
	 *  This function should return a text description of the screen/tab
	 *
	 * @since 20170505
	 */
	abstract public function describe_options();
	/**
	 *  This function should return the screen/tab layout array
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
	 *  Add options layout to current form.
	 *
	 * @since 20170505
	 * @param  array $form  Contains form information determining the screen layout.
	 * @return array        Returns the form with a tab layout added.
	 */
	public function form_layout( $form ) {
		if ( ! array_key_exists( $this->base, $form ) ) {
			$form[ $this->base ] = $this->default_form_layout();
		}
		return $form;
	}

	/**
	 *  Set the screen property and return it.
	 *
	 * @since 20170505
	 * @return array  Current screen layout.
	 */
	public function default_form_layout() {
		if ( empty( $this->screen ) ) {
			$this->screen = array(
				'describe' => [ $this, 'describe_options' ],
				'title'    => $this->form_title(),
				'icon'     => $this->form_icon(),
				'option'   => 'tcc_options_' . $this->base,
				'layout'   => apply_filters( "tcc_{$this->base}_options_layout", $this->options_layout() ),
			);
		}
		return $this->screen;
	}

	/**
	 *  Add showhide data to array passed to javascript
	 *
	 * @since 20170505
	 * @param  array $data  Incoming data.
	 * @return array        Data with showhide data added.
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
	 *  Create an array containing default option values.
	 *
	 * @since 20170505
	 * @return array  Current screen default values.
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
	 *  Get the layout for a screen item.
	 *
	 * @since 20180410
	 * @param string $item  Slug of the item to be retrieved.
	 * @return array        Layout of the item requested.
	 */
	public function get_item( $item ) {
		$layout = ( empty( $this->screen ) ) ? $this->options_layout() : $this->screen['layout'];
		return ( array_key_exists( $item, $layout ) ) ? $layout[ $item ] : array();
	}


}
