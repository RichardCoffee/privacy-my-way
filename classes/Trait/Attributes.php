<?php

trait PMW_Trait_Attributes {


	public function apply_attrs( $args ) {
		echo $this->get_apply_attrs( $args );
	}

	public function apply_attrs_tag( $attrs, $html_tag ) {
		echo $this->get_apply_attrs_tag( $attrs, $html_tag );
	}

	public function get_apply_attrs( $args ) {
		$attrs = ' ';
		foreach( $args as $attr => $value ) {
			if ( empty( $value ) ) {
				if ( in_array( $attr, array( 'itemscope', 'value' ), true ) ) {
					$attrs .= $attr . '="" ';
				}
				continue;
			}
			switch( $attr ) {
				case 'action':
				case 'href':
				case 'itemtype':	#	schema.org
				case 'src':
					$value = esc_url( $value );
					break;
				case 'value':
					$value = esc_html( $value );
					break;
				case 'aria-label':
				case 'placeholder':
				case 'title':
					$value = wp_strip_all_tags( $value );
				default:
					$value = esc_attr( $value );
			}
			$attrs .= $attr . '="' . $value . '" ';
		}
		return $attrs;
	}

	public function get_apply_attrs_tag( $attrs, $html_tag ) {
		$html = '<' . $html_tag . $this->get_apply_attrs( $attrs );
		$html .= ( $this->is_self_closing( $html_tag ) ) ? ' />' : '>';
		return $html;
	}

	private function is_self_closing( $tag ) {
		$self_closing = array( 'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr' );
		return in_array( $tag, $self_closing, true );
	}


}
