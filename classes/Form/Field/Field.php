<?php

/*
 *  File:   classes/Form/Field/Field.php
 *
 */

abstract class PMW_Form_Field_Field {

#	protected $echo       = true;       # echo html
	protected $field_css  = '';         # field css
	protected $field_default;           # default value
	protected $field_help = '';         # used for tooltip text
	protected $field_id;                # field id
	protected $field_name;              # field name
	protected $field_postext = '';      # text shown below input
	protected $field_pretext = '';      # text shown above input
	protected $field_type = 'text';     # input type
	protected $field_value;             # field value
	protected $label_css  = '';         # label css
	protected $label_text = '';         # label text
	protected $library;                 # plugin function library
	protected $onchange = null;         # onchange attribute
	protected $placeholder = '';        # placeholder text
#	protected $post_id;                 # wordpress post id number
	protected $sanitize   = 'esc_attr'; # default sanitize method

	use TCC_Trait_Magic;
	use TCC_Trait_ParseArgs;

	public function __construct( $args ) {
		$this->library = new PMW_Plugin_Library;
		$this->parse_args( $args );
		if ( ( empty( $this->placeholder ) ) && ( ! empty( $this->label_text ) ) ) {
			$this->placeholder = $this->label_text;
		}
		if ( empty( $this->field_id ) ) {
			$this->field_id = $this->field_name;
		}
	}

	public function input( $label = true ) {
		$attrs = array(
			'id'          => $this->field_id,
			'type'        => $this->field_type,
			'class'       => $this->field_css,
			'name'        => $this->field_name,
			'value'       => $this->field_value,
			'placeholder' => $this->placeholder,
		); ?>
		<input <?php $this->library->apply_attrs( $attrs ); ?> /><?php
	}

	protected function label() {
		$attrs = array(
			'class' => $this->label_css,
			'for'   => $this->field_id,
		);
		$label  = '<label ' . $this->library->get_apply_attrs( $attrs ) . '>';
		$label .= esc_html( $this->label_text );
		$label .= '</label>';
		return $label;
	}

	public function sanitize( $input ) {
		if ( $this->sanitize ) {
			$sanitize = $this->sanitize;
			$output   = $sanitize( $input );
		} else {
			$output = strip_tags( stripslashes( $input ) );
		}
		return $output;
	}

}
