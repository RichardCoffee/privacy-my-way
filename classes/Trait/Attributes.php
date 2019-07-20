<?php
/**
 * classes/Trait/Attributes.php
 *
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 */
/**
 * A trait that provides methods to generate html for tag attributes
 *
 * @since 20170506
 * @link 4.9.5:wp-includes/general-template.php:2949
 */
trait PMW_Trait_Attributes {

	/**
	 *  alias for apply_attrs_element method
	 *
	 * @since 20180426
	 * @param string $tag
	 * @param array $attrs
	 * @param string $text
	 * @param boolean $raw if true will prevent $text from being escaped when displayed
	 */
	public function element( $tag, $attrs, $text = '', $raw = false ) {
		echo $this->get_apply_attrs_element( $tag, $attrs, $text, $raw );
	}

	/**
	 *  alias for get_apply_attrs_element method
	 *
	 * @since 20180426
	 * @param string $tag
	 * @param array $attrs
	 * @param string $text
	 * @param boolean $raw if true will prevent $text from being escaped when displayed
	 * @return string
	 * @used-by PMW_Form_Admin::field_label()
	 */
	public function get_element( $tag, $attrs, $text = '', $raw = false ) {
		return $this->get_apply_attrs_element( $tag, $attrs, $text, $raw );
	}

	/**
	 *  alias for apply_attrs_tag method
	 *
	 * @since 20180426
	 * @param string $tag
	 * @param array $attrs
	 */
	public function tag( $tag, $attrs ) {
		echo $this->get_apply_attrs_tag( $tag, $attrs );
	}

	/**
	 *  alias for get_apply_attrs_tag method
	 * @since 20180426
	 * @param string $tag
	 * @param array $attrs
	 * @return string
	 */
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
		echo $this->get_apply_attrs( $attrs );
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
		foreach( $attrs as $key => $value ) {
			$attr = sanitize_key( $key );
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
					break;
				default:
					$value = esc_attr( $value );
			}
			$html .= ' ' . $attr . '="' . $value . '"';
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
	 * generates the initial html for the desired tag and attributes
	 *
	 * @since 20170506
	 * @param string $html_tag tag to be generated
	 * @param array $attrs contains attribute/value pairs
	 * @return string
	 */
	public function get_apply_attrs_tag( $html_tag, $attrs ) {
		$attrs = $this->filter_attributes_by_tag( $html_tag, $attrs );
		$html  = '<' . $this->sanitize_tag( $html_tag );
		$html .= $this->get_apply_attrs( $attrs );
		$html .= ( $this->is_tag_self_closing( $html_tag ) ) ? ' />' : '>';
		return $html;
	}

	/**
	 *  sanitize element tag
	 *
	 * @since 20180829
	 * @param string $tag
	 * @return string
	 */
	protected function sanitize_tag( $tag ) {
		$tag = strtolower( $tag );
		$tag = preg_replace( '/[^a-z0-9]/', '', $tag );
		return $tag;
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
	 * @param boolean $raw if true will prevent $text from being escaped when displayed
	 * @return string
	 */
	public function get_apply_attrs_element( $element, $attrs, $text = '', $raw = false ) {
		$element = $this->sanitize_tag( $element );
		$attrs   = $this->filter_attributes_by_tag( $element, $attrs );
		$html    = "<$element" . $this->get_apply_attrs( $attrs );
		$inner   = ( $raw ) ? $text : esc_html( $text );
		if ( $this->is_tag_self_closing( $element ) ) {
			$html .= ' />' . $inner;
		} else {
			$html .= '>' . $inner . "</$element>";
		}
		return $html;
	}

	/**
	 *  filter the attribute array by the html tag and the array subscript
	 *
	 * @since 20180425
	 * @link https://www.hongkiat.com/blog/wordpress-rel-noopener/
	 * @param string $html_tag
	 * @param array $attrs
	 * @return array
	 */
	public function filter_attributes_by_tag( $html_tag, $attrs ) {
		if ( ( $html_tag === 'a' ) && isset( $attrs[ 'target' ] ) ) {
			$attrs['rel'] = ( ( isset( $attrs['rel'] ) ) ? $attrs['rel'] . ' ' : '' ) . 'nofollow noopener';
#			$attrs['rel'] = apply_filters( 'fluid_filter_attributes_by_a_rel', $attrs['rel'], $attrs );
		}
		return $attrs;
	}


/***   helper functions   ***/

	/**
	 *  add the checked attribute to the attributes array
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs
	 * @param mixed $checked value to check
	 * @param mixed $current base value to check against
	 * @return array
	 */
	public function checked( $attrs, $checked, $current = true ) {
		return $this->checked_selected_helper( $attrs, $checked, $current, 'checked' );
	}

	/**
	 *  add the disabled attribute to the attributes array
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs
	 * @param mixed $disabled value to check
	 * @param mixed $current base value to check against
	 * @return array
	 */
	public function disabled( $attrs, $disabled, $current = true ) {
		return $this->checked_selected_helper( $attrs, $disabled, $current, 'disabled' );
	}

	/**
	 *  add the readonly attribute to the attributes array
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs
	 * @param mixed $readonly value to check
	 * @param mixed $current base value to check against
	 * @return array
	 */
	public function readonly( $attrs, $readonly, $current = true ) {
		return $this->checked_selected_helper( $attrs, $readonly, $current, 'readonly' );
	}

	/**
	 *  add the selected attribute to the attributes array
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs
	 * @param mixed $selected value to check
	 * @param mixed $current base value to check against
	 * @return array
	 */
	public function selected( $attrs, $selected, $current = true ) {
		return $this->checked_selected_helper( $attrs, $selected, $current, 'selected' );
	}

	/**
	 *  workhorse of the checked, disabled, readonly, and selected methods
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs
	 * @param mixed $checked value to check
	 * @param mixed $current base value to check against
	 * @param string $type attribute to add
	 * @return array
	 */
	protected function checked_selected_helper( $attrs, $helper, $current, $type ) {
		if ( (string) $helper === (string) $current ) {
			$attrs[ $type ] = $type;
		}
		return $attrs;
	}


}
