<?php

/*
 *  File:   classes/Form/Field/Admin.php
 *
 *  note:  register_setting calls need to be updated to WP4.7
 */

class PMW_Form_Field_Admin extends PMW_Form_Field_Field {

	protected $action   = 'admin_init';  #  when to register variable
	protected $callback = null;          #  display method
	protected $default  = '';
	protected $group;
	protected $restapi = true;

	public function __construct( $args ) {
		parent::__construct( $args );
		if ( empty( $this->field_value ) ) {
			$possible = get_option( $this->field_name );
			if ( $possible ) {
				$this->field_value = $this->sanitize( $possible );
			}
		}
		if ( empty( $this->field_value) && ! empty( $this->field_default ) ) {
			$this->field_value = $this->sanitize( $this->field_default );
		}
		if ( empty( $this->callback ) ) {
			$this->callback = array( $this, 'input' );
		}
		add_action( $this->action, array( $this, 'register_field' ), 9 );
	}

	public function register_field() {
		if ( ! empty( $this->group ) ) {
			$args = array(
				'type'              => $this->field_type,
				'group'             => $this->group,
				'description'       => $this->label_text,
				'sanitize_callback' => $this->sanitize,
				'show_in_rest'      => $this->restapi,
			);
			register_setting( $this->group, $this->field_name, $args );
			add_settings_field( $this->field_name, $this->label(), $this->callback, $this->group );
		}
	}


}
