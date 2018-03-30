<?php

class PMW_Form_Field_Radio extends PMW_Form_Field_Field {

	protected $options  = array();
	protected $sanitize = 'sanitize_title';
	protected $type     = 'radio';

	public function radio() {
		if ( $this->options ) {
			$attrs = array(
				'type'  => $this->type,
				'name'  => $this->field_name,
			);
			if ( $this->onchange ) {
				$attrs['onchange'] = $this->onchange;
			} ?>
			<div title="<?php echo esc_attr( $this->field_help ); ?>"><?php
				if ( $this->field_pretext ) {
					$uniq = 'radio_' . uniqid(); ?>
					<div id="<?php echo $uniq; ?>">
						<?php echo esc_html( $this->field_pretext ); ?>
					</div><?php
					$attrs['aria-describedby'] = $uniq;
				}
				foreach( $this->options as $key => $text ) {
					$attrs['value'] = $key; ?>
					<div>
						<label>
							<input <?php fluid()->apply_attrs( $attrs ); ?> <?php checked( $this->field_value, $key ); ?>><?php
							echo esc_html( $text ); ?>
						</label>
					</div><?php
				}
				if ( $this->field_postext ) { ?>
					<div>
						<?php echo esc_html( $this->field_postext ) ; ?>
					</div><?php
				} ?>
			</div><?php
		}
	}


}

