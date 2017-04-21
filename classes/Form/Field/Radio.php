<?php

class PMW_Form_Field_Radio extends PMW_Form_Field_Field {

	protected $field_radio = array();
	protected $sanitize    = 'sanitize_title';
	protected $field_type  = 'radio';

	public function radio() {
		if ( $this->field_radio ) {
			$attrs = array(
				'type'  => $this->field_type,
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
				foreach( $this->field_radio as $key => $text ) {
					$attrs['value'] = $key; ?>
					<div>
						<label>
							<input <?php $this->library->apply_attrs( $attrs ); ?> <?php checked( $this->field_value, $key ); ?>><?php
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

