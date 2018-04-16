<?php

class PMW_Form_Field_Radio extends PMW_Form_Field_Field {

	public    $choices = array();
	protected $type    = 'radio';
	protected $field_postext = '';      # text shown below input
	protected $field_pretext = '';      # text shown above input

	public function __construct( $args ) {
		parent::__construct( $args );
		$this->sanitize = array( $this, 'sanitize' );
	}

	public function radio() {
		if ( $this->choices ) {
			$attrs = array(
				'type'  => $this->type,
				'name'  => $this->field_name,
			);
			if ( $this->onchange ) {
				$attrs['onchange'] = $this->onchange;
			} ?>
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
					<div>
						<label>
							<input <?php fluid()->apply_attrs( $attrs ); ?> <?php checked( $this->field_value, $key ); ?>><?php
							echo esc_html( $text ); ?>
						</label>
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

	# See also: classes/Form/Sanitizer.php
	public function sanitize( $input ) {
		$input = sanitize_key( $input );
		return ( array_key_exists( $input, $this->choices ) ? $input : $this->default );
	}


}

