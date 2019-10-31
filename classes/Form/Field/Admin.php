<?php
/*
 *  File:   classes/Form/Field/Admin.php
 *
 */
defined( 'ABSPATH' ) || exit;

class PMW_Form_Field_Admin extends PMW_Form_Field_Field {

	protected $action   = 'admin_head';  #  when to register variable - must happen after current_screen hook
	protected $callback = null;          #  display method
	protected $default  = '';            #  field default value
	protected $group;                    #  setting group/page
	protected $section  = 'default';     #  section on page
	protected $show_in_rest = true;      #  allow access via rest api
	protected $tr_class = '';            #  <tr> css class

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
		add_action( $this->action, array( $this, 'register_field' ), 9 );
	}

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
