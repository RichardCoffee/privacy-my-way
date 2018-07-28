<?php
/**
 * classes/Trait/Attributes.php
 *
 */
/**
 * A trait that provides methods to generate html for tag attributes
 *
 * @since 20170506
 * @link 4.9.5:wp-includes/general-template.php:2949
 */
trait PMW_Trait_Attributes {

	/**
	 * alias for apply_attrs_element
	 *
	 * @since 20180426
	 * @param string $tag
	 * @param array $attrs
	 * @param string $text
	 */
	public function element( $tag, $attrs, $text = '' ) {
		$this->apply_attrs_element( $tag, $attrs, $text );
	}

#	 * @since 20180426
	public function get_element( $tag, $attrs, $text = '' ) {
		return $this->get_apply_attrs_element( $tag, $attrs, $text );
}

#	 * @since 20180426
	public function tag( $tag, $attrs ) {
		$this->apply_attrs_tag( $tag, $attrs );
	}

#	 * @since 20180426
	public function get_tag( $tag, $attrs ) {
		return $this->get_apply_attrs_tag( $tag, $attrs );
	}

	/**
	 * echo the generated html attributes
	 *
	 * @since 20170506
	 * @param array $attrs an associative array containing the attribute keys and values
	 */
	public function apply_attrs( $attrs ) {
		echo wp_kses( $this->get_apply_attrs( $attrs ), [ ] );
	}

	/**
	 * generates the html for the tag attributes
	 *
	 * @since 20170506
	 * @param array $attrs contains attribute/value pairs
	 * @return string
	 */
	public function get_apply_attrs( $attrs ) {

/*		static $is_allowed_no_value = null;
		if ( empty( $is_allowed_no_value ) ) {
			$is_allowed_no_value = apply_filters( 'fluid_attr_is_allowed_no_value', [ 'itemscope', 'value' ] );
		} //*/
		$is_allowed_no_value = array( 'itemscope', 'multiple', 'value', 'required' );

		$html = '';
		foreach( $attrs as $attr => $value ) {
			if ( empty( $value ) ) {
				if ( in_array( $attr, $is_allowed_no_value, true ) ) {
					$html .= "$attr ";
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
				case 'rel':
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
	 * @since 20170510
	 * @param string|array $classes css classes to be sanitized
	 * @return string
	 */
	public function sanitize_html_class( $classes ) {
		if ( is_array( $classes ) ) {
			// pack it down then blow it up - insure each item is a single class
			$classes = explode( ' ', implode( ' ', $classes ) );
		} else {
			// convert string to an array
			$classes = explode( ' ', $classes );
		}
		return implode( ' ', array_map( 'sanitize_html_class', array_unique( $classes ) ) );
	}

	/**
	 * echo the generated tag html
	 *
	 * @since 20170507
	 * @param string $html_tag the tag to be generated
	 * @param array $attrs an associative array containing the attribute keys and values
	 */
	public function apply_attrs_tag( $tag, $attrs ) {
		echo $this->get_apply_attrs_tag( $tag, $attrs );
	}

	/**
	 * generates the initial html for the desired tag and attributes
	 *
	 * @since 20170506
	 * @param string $html_tag tag to be generated
	 * @param array $attrs contains attribute/value pairs
	 * @return string
	 */
	public function get_apply_attrs_tag( $html_tag, $attrs ) {
		$attrs = $this->filter_attributes_by_tag( $html_tag, $attrs );
		$html  = "<$html_tag ";
		$html .= $this->get_apply_attrs( $attrs );
		$html .= ( $this->is_tag_self_closing( $html_tag ) ) ? ' />' : '>';
		return $html;
	}

	/**
	 * checks for tags that are self closing
	 *
	 * @since 20170507
	 * @param string $tag tag to check for
	 * @return bool
	 */
	protected function is_tag_self_closing( $tag ) {
		static $self_closing;
		if ( ! $self_closing ) {
			$self_closing = array( 'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr' );
			$self_closing = apply_filters( 'fluid_is_tag_self_closing', $self_closing );
		}
		return in_array( $tag, $self_closing, true );
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
	 * generates the html for the element with enclosed content
	 *
	 * @since 20180408
	 * @param string $element element to be generated
	 * @param array $attrs contains attribute/value pairs
	 * @param string $text content of html element
	 * @return string
	 */
	public function get_apply_attrs_element( $element, $attrs, $text = '' ) {
		$attrs = $this->filter_attributes_by_tag( $element, $attrs );
		$html  = "<$element ";
		$html .= $this->get_apply_attrs( $attrs );
		if ( $this->is_tag_self_closing( $element ) ) {
			$html .= ' />' . esc_html( $text );
		} else {
			$html .= '>' . esc_html( $text ) . "</$element>";
		}
		return $html;
	}

#	 * @since 20180425
#	 * @link https://www.hongkiat.com/blog/wordpress-rel-noopener/
	public function filter_attributes_by_tag( $html_tag, $attrs ) {
		if ( ( $html_tag === 'a' ) && isset( $attrs[ 'target' ] ) ) {
			$attrs['rel'] = ( ( isset( $attrs['rel'] ) ) ? $attrs['rel'] . ' ' : '' ) . 'nofollow noopener';
#			$attrs['rel'] = apply_filters( 'fluid_filter_attributes_by_a_rel', $attrs['rel'], $attrs );
		}
		return $attrs;
	}


/***   helper functions   ***/

#	 * @since 20180424
	public function checked( $attrs, $checked, $current = true ) {
		return $this->checked_selected_helper( $attrs, $checked, $current, 'checked' );
	}

#	 * @since 20180424
	public function disabled( $attrs, $disabled, $current = true ) {
		return $this->checked_selected_helper( $attrs, $disabled, $current, 'disabled' );
	}

#	 * @since 20180424
	public function readonly( $attrs, $readonly, $current = true ) {
		return $this->checked_selected_helper( $attrs, $readonly, $current, 'readonly' );
	}

#	 * @since 20180424
	public function selected( $attrs, $selected, $current = true ) {
		return $this->checked_selected_helper( $attrs, $selected, $current, 'selected' );
	}

#	 * @since 20180424
	protected function checked_selected_helper( $attrs, $helper, $current, $type ) {
		if ( (string) $helper === (string) $current ) {
			$attrs[ $type ] = $type;
		}
		return $attrs;
	}


}
