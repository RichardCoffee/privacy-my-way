<?php

/*
 *  File:  classes/Form/Field/Field.php
 *
 *  Note:  The sanitize callback may be called twice, as per https://core.trac.wordpress.org/ticket/21989
 */

abstract class PMW_Form_Field_Field {

	protected $field_css     = '';      # field css
	protected $field_default = '';      # default value
	protected $field_help    = '';      # used for tooltip text
	protected $field_id      = '';      # field id
	protected $field_name    = '';      # field name
	protected $field_postext = '';      # text shown below input
	protected $field_pretext = '';      # text shown above input
	protected $type          = 'text';  # input type
	protected $field_value = '';        # field value
	protected $label_css   = '';        # label css
	protected $description = '';        # label text
	protected $onchange    = null;      # onchange attribute
	protected $placeholder = '';        # placeholder text
#	protected $post_id;                 # wordpress post id number
	protected $sanitize   = 'esc_attr'; # default sanitize method
	protected $see_label  = true;       # is the label visible?
	protected $form_control = true;     # add form-control css

	protected static $date_format = 'm/d/y';

	use PMW_Trait_Magic;
	use PMW_Trait_ParseArgs;

	public function __construct( $args = array() ) {
#		if ( empty( self::$date_format ) ) {
#			self::$date_format = get_option( 'date_format' );
#		}
		$this->parse_args( $args );
		if ( ( empty( $this->placeholder ) ) && ( ! empty( $this->description ) ) ) {
			$this->placeholder = $this->description;
		}
		if ( empty( $this->field_id ) ) {
			$this->field_id = $this->field_name;
		}
		if ( $this->form_control ) {
			$this->add_form_control_css();
		}
	}

	public function get_date_format() {
		return self::$date_format;
	}

	public function input() {
		echo $this->get_input();
	}

	public function get_input() {
		$attrs = array(
			'id'          => $this->field_id,
			'type'        => $this->type,
			'class'       => $this->field_css,
			'name'        => $this->field_name,
			'value'       => $this->field_value,
			'placeholder' => $this->placeholder,
		);
		return $this->get_apply_attrs_tag( 'input', $attrs );
	}

	protected function label() {
		echo $this->get_label();
	}

	protected function get_label() {
		if ( empty( $this->description ) ) {
			return '';
		}
		$attrs = array(
			'id'    => $this->field_id . '_label',
			'class' => $this->label_css . ( ! $this->see_label ) ? ' screen-reader-text' : '',
			'for'   => $this->field_id,
		);
		return $this->get_apply_attrs_element( 'label', $attrs, $this->description );
	}

	protected function add_form_control_css( $new = 'form-control' ) {
		$css = explode( ' ', $this->field_css );
		if ( ! in_array( $new, $css ) ) {
			$css[] = $new;
		}
		$this->field_css = implode( ' ', $css );
	}

	public function sanitize( $input ) {
		if ( $this->sanitize && is_callable( $this->sanitize ) ) {
			$output = call_user_func( $this->sanitize, $input );
		} else {
			$output = wp_strip_all_tags( stripslashes( $input ) );
		}
		return $output;
	}

}
