<?php

class PMW_Form_Field_Radio extends PMW_Form_Field_Field {

	public    $choices       =  array();
	protected $type          = 'radio';
	protected $field_postext = '';      # text shown below input
	protected $field_pretext = '';      # text shown above input

	public function __construct( $args ) {
		parent::__construct( $args );
		$this->sanitize = array( $this, 'sanitize' );
	}

	public function radio_table_row() { ?>
		<tr>
			<th><?php
				$this->label(); ?>
			</th>
			<td><?php
				$this->radio(); ?>
			</td>
		</tr><?php
	}

	public function radio() {
		if ( $this->choices ) {
			$attrs = $this->get_radio_element_attributes(); ?>
			<div title="<?php echo esc_attr( $this->field_help ); ?>"><?php
/*				if ( $this->field_pretext ) {
					$uniq = 'radio_' . uniqid(); ?>
					<div id="<?php echo $uniq; ?>">
						<?php echo esc_html( $this->field_pretext ); ?>
					</div><?php
					$attrs['aria-describedby'] = $uniq;
				} //*/
				foreach( $this->choices as $key => $text ) {
					$attrs['value'] = $key; ?>
					<div><?php
						$this->display_radio_element( $attrs, $text ); ?>
					</div><?php
				}
/*				if ( $this->field_postext ) { ?>
					<div>
						<?php echo esc_html( $this->field_postext ) ; ?>
					</div><?php
				} //*/ ?>
			</div><?php
		}
	}

	protected function get_radio_element_attributes() {
		return array(
			'type' => $this->type,
			'name' => $this->field_name,
			'onchange' => $this->onchange,
		);
	}

	public function display_radio_element( $attrs, $text ) { ?>
		<label><?php
			$atts = $this->checked( $attrs, $attrs['value'], $this->field_value );
			$this->element( 'input', $atts, $text ); ?>
		</label><?php
	}

	# See also: classes/Form/Sanitizer.php
	public function sanitize( $input ) {
		$input = sanitize_key( $input );
		return ( array_key_exists( $input, $this->choices ) ? $input : $this->default );
	}


}

