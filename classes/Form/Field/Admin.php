<?php
/**
 *  Display admin forms
 *
 * @package Privacy_My_Way
 * @subpackage Forms
 * @since 20180323
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Form/Field/Admin.php
 */
defined( 'ABSPATH' ) || exit;
/**
 *  Provides properties and methods useful for registering fields on admin screens
 *
 */
class PMW_Form_Field_Admin extends PMW_Form_Field_Field {

	/**
	 * @since 20180323
	 * @var string When to register variable - must happen after current_screen hook
	 */
	protected $action = 'admin_head';
	/**
	 * @since 20180323
	 * @var string|array display method to be used
	 */
	protected $callback = null;
	/**
	 * @since 20180323
	 * @var string field default value
	 */
	protected $default = '';
	/**
	 * @since 20180323
	 * @var string setting group/page
	 */
	protected $group;
	/**
	 * @since 20180326
	 * @var string section on page
	 */
	protected $section = 'default';
	/**
	 * @since 20180326
	 * @var bool allow access via rest api
	 */
	protected $show_in_rest = true;
	/**
	 * @since 20180326
	 * @var string css class for table row
	 */
	protected $tr_class = '';

	/**
	 *  Constructor function
	 *
	 * @since 20180323
	 * @param array field behavior values
	 * @see get_option()
	 * @see add_action()
	 */
	public function __construct( $args ) {
		parent::__construct( $args );
		if ( empty( $this->value ) ) {
			$possible = get_option( $this->name );
			if ( $possible ) {
				$this->field = $possible;
			}
		}
		if ( empty( $this->value) && ! empty( $this->default ) ) {
			$this->value = $this->default;
		}
		if ( empty( $this->callback ) ) {
			$this->callback = array( $this, 'input' );
		}
		add_action( $this->action, [ $this, 'register_field' ], 9 );
	}

	/**
	 *  Register the field
	 *
	 * @since 20180323
	 * @uses PMW_Form_Field_Field::get_label()
	 * @see register_settings()
	 * @see add_settings_field()
	 */
	public function register_field() {
		if ( ! empty( $this->group ) ) {
			$args = array(
				'type'              => $this->type,
				'group'             => $this->group,
				'description'       => $this->description,
				'sanitize_callback' => $this->sanitize,
				'show_in_rest'      => $this->show_in_rest,
				'default'           => $this->default,
			);
			register_setting( $this->group, $this->name, $args );
			$opts = array();
			if ( empty( $this->label_css ) ) {
				#  use wordpress to create label
				$label = $this->description;
				$opts['label_for'] = $this->id;
			} else {
				#  create our own label
				$label = $this->get_label();
			}
			if ( ! empty( $this->tr_class) ) {
				$opts['class'] = $this->tr_class;
			}
			add_settings_field( $this->name, $label, $this->callback, $this->group, $this->section, $opts );
		}
	}


}
