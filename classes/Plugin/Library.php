<?php

class PMW_Plugin_Library {


	/**  attribute functions  **/

	public function apply_attrs( $args ) {
		echo $this->get_apply_attrs( $args );
	}

	public function get_apply_attrs( $args ) {
		$attrs = ' ';
		foreach( $args as $attr => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			switch( $attr ) {
				case 'href':
				case 'src':
					$value = esc_url( $value );
					break;
				case 'value':
					$value = esc_html( $value );
					break;
				case 'aria-label':
				case 'title':
					$value = wp_strip_all_tags( $value );
				default:
					$value = esc_attr( $value );
			}
			$attrs .= $attr . '="' . $value . '" ';
		}
		return $attrs;
	}


}
