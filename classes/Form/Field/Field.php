<?php

/*
 *  File:  classes/Form/Field/Field.php
 *
 *  Note:  The sanitize callback may be called twice, as per https://core.trac.wordpress.org/ticket/21989
 */

abstract class PMW_Form_Field_Field {

	protected $field_css  = '';         # field css
	protected $field_default;           # default value
	protected $field_help = '';         # used for tooltip text
	protected $field_id;                # field id
	protected $field_name;              # field name
	protected $field_postext = '';      # text shown below input
	protected $field_pretext = '';      # text shown above input
	protected $type          = 'text';  # input type
	protected $field_value;             # field value
	protected $label_css   = '';        # label css
	protected $description = '';        # label text
	protected $onchange    = null;      # onchange attribute
	protected $placeholder = '';        # placeholder text
#	protected $post_id;                 # wordpress post id number
	protected $sanitize   = 'esc_attr'; # default sanitize method

	use PMW_Trait_Magic;
	use PMW_Trait_ParseArgs;

	public function __construct( $args ) {
		$this->parse_args( $args );
		if ( ( empty( $this->placeholder ) ) && ( ! empty( $this->description ) ) ) {
			$this->placeholder = $this->description;
		}
		if ( empty( $this->field_id ) ) {
			$this->field_id = $this->field_name;
		}
	}

	public function input() {
		$attrs = array(
			'id'          => $this->field_id,
			'type'        => $this->type,
			'class'       => $this->field_css,
			'name'        => $this->field_name,
			'value'       => $this->field_value,
			'placeholder' => $this->placeholder,
		); ?>
		<input <?php fluid()->apply_attrs( $attrs ); ?> /><?php
	}

	protected function label() {
		$attrs = array(
			'class' => $this->label_css,
			'for'   => $this->field_id,
		);
		$label  = '<label ' . fluid()->get_apply_attrs( $attrs ) . '>';
		$label .= esc_html( $this->description );
		$label .= '</label>';
		return $label;
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
