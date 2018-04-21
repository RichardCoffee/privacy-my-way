<?php
/**
 * classes/Trait/Attributes.php
 *
 */
/**
 * A trait that provides methods to generate html for tag attributes
 *
 * @link 4.9.5:wp-includes/general-template.php:2949
 */
trait PMW_Trait_Attributes {

	/**
	 * echo the generated html attributes
	 *
	 * @param array $attrs an associative array containing the attribute keys and values
	 */
	public function apply_attrs( $attrs ) {
		echo $this->get_apply_attrs( $attrs );
	}

	/**
	 * echo the generated html
	 *
	 * @since 20180408
	 * @param string $element
	 * @param array $attrs
	 * @param string $text
	 */
	public function apply_attrs_element( $element, $attrs, $text = '' ) {
		echo $this->get_apply_attrs_element( $element, $attrs, $text );
	}

	/**
	 * echo the generated tag html
	 *
	 * @param string $html_tag the tag to be generated
	 * @param array $attrs an associative array containing the attribute keys and values
	 */
	public function apply_attrs_tag( $html_tag, $attrs ) {
		echo $this->get_apply_attrs_tag( $html_tag, $attrs );
	}

	/**
	 * generates the html for the tag attributes
	 *
	 * @param array $attrs contains attribute/value pairs
	 * @return string
	 */
	public function get_apply_attrs( $attrs ) {

		$is_allowed_no_value = array( 'itemscope', 'value' );
/*		static $is_allowed_no_value;
		if ( ! $is_allowed_no_value ) {
			$is_allowed_no_value = apply_filters( 'fluid_attr_is_allowed_no_value', [ 'itemscope', 'value' ] );
		} //*/

		$html = ' ';
		foreach( $attrs as $attr => $value ) {
			if ( empty( $value ) ) {
				if ( in_array( $attr, $is_allowed_no_value, true ) ) {
					$html .= $attr . '="" ';
				}
				continue;
			}
			switch( $attr ) {
				case 'action':
				case 'href':
				case 'itemtype': # schema.org
				case 'src':
					# https://konstantin.blog/2012/esc_url-vs-esc_url_raw/
					$value = esc_url( $value );
					break;
				case 'class':
					$value = $this->sanitize_html_class( $value );
					break;
				case 'value':
					$value = esc_html( $value );
					break;
				case 'aria-label':
				case 'placeholder':
				case 'title':
					$value = esc_attr( wp_strip_all_tags( $value ) );
				default:
					$value = esc_attr( $value );
			}
			$html .= $attr . '="' . $value . '" ';
		}
		return $html;
	}

	/**
	 * applys the wordpress function sanitize_html_class to a string containing multiple css classes
	 *
	 * @param string|array $classes css classes to be sanitized
	 * @return string
	 */
	private function sanitize_html_class( $classes ) {
		if ( is_array( $classes ) ) {
			// pack it down then blow it up - insure each element is a single class
			$classes = array_unique( explode( ' ', implode( ' ', $classes ) ) );
		} else {
			// convert string to an array
			$classes = explode( ' ', $classes );
		}
		return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
	}

	/**
	 * generates the initial html for the desired tag and attributes
	 *
	 * @param string $html_tag tag to be generated
	 * @param array $attrs contains attribute/value pairs
	 * @return string
	 */
	public function get_apply_attrs_tag( $html_tag, $attrs ) {
		$html  = "<$html_tag ";
		$html .= $this->get_apply_attrs( $attrs );
		$html .= ( $this->is_self_closing( $html_tag ) ) ? ' />' : '>';
		return $html;
	}

	/**
	 * checks for tags that are self closing
	 *
	 * @param string $tag tag to check for
	 * @return bool
	 */
	private function is_self_closing( $tag ) {
		static $self_closing;
		if ( ! $self_closing ) {
			$self_closing = array( 'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr' );
			$self_closing = apply_filters( 'fluid_tag_is_self_closing', $self_closing );
		}
		return in_array( $tag, $self_closing, true );
	}

	/**
	 * generates the html for the element with enclosed content
	 *
	 * @since 20180408
	 * @param string $element element to be generated
	 * @param array $attrs contains attribute/value pairs
	 * @param string $text content of html element
	 * @return string
	 */
	public function get_apply_attrs_element( $element, $attrs, $text = '' ) {
		$html  = "<$element ";
		$html .= $this->get_apply_attrs( $attrs );
		if ( $this->is_self_closing( $element ) ) {
			$html .= ' />';
		} else {
			$html .= '>' . esc_html( $text ) . "</$element>";
		}
		return $html;
	}


}
