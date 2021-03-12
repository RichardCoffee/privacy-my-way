<?php
/**
 *  A trait that provides methods to generate HTML elements with sanitized attributes.
 *
 * @package Privacy_My_Way
 * @subpackage Traits
 * @since 20170506
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Attributes.php
 * @link 4.9.5:wp-includes/general-template.php:2949
 * @link https://github.com/OWASP/CheatSheetSeries/blob/master/cheatsheets/HTML5_Security_Cheat_Sheet.md
 */
defined( 'ABSPATH' ) || exit;


trait PMW_Trait_Attributes {


	/***  Properties  ***/

	/**
	 * @since 20191213
	 * @var bool  Flag to force sandbox attribute for iframes tag.
	 */
	protected static $attr_iframe_sandbox = false;
	/**
	 * @since 20200313
	 * @var string  Nonce to be added for javascript events.
	 */
	private static $attr_javascript_nonce = '';


	/***  Methods  ***/

	/**
	 *  Display an HTML element.
	 *
	 * @since 20180426
	 * @param string $tag    Tag for the HTML element.
	 * @param array  $attrs  Attributes for the element.
	 * @param string $text   Text to appear between the opening and closing tags.
	 * @param bool   $raw    Set this to true to prevent $text from being escaped when displayed.  Use this at your own risk.
	 */
	public function element( $tag, $attrs, $text = '', $raw = false ) {
		echo $this->get_element( $tag, $attrs, $text, $raw );
	}

	/**
	 *  Return an HTML element.
	 *
	 * @since 20180408
	 * @param string $tag    Tag for the HTML element.
	 * @param array  $attrs  Attributes to be applied to element.
	 * @param string $text   Text to appear between the opening and closing tags.
	 * @param bool   $raw    Set this to true to prevent $text from being escaped when displayed.  Use this at your own risk.
	 * @return string        An HTML element in string form.
	 */
	public function get_element( $tag, $attrs, $text = '', $raw = false ) {
		$tag   = $this->sanitize_tag( $tag );
		$attrs = $this->filter_attributes_by_tag( $tag, $attrs );
		$html  = "<$tag" . $this->get_apply_attrs( $attrs );
		$inner = ( $raw ) ? $text : esc_html( $text );
		if ( $this->is_tag_self_closing( $tag ) ) {
			$html .= ' />' . $inner;
		} else {
			$html .= '>' . $inner . "</$tag>";
		}
		return $html;
	}

	/**
	 *  Display a self-closing HTML element.
	 *
	 * @since 20180426
	 * @param string $tag    Tag for the HTML element.
	 * @param array  $attrs  Attributes to be applied to element.
	 */
	public function tag( $tag, $attrs ) {
		echo $this->get_tag( $tag, $attrs );
	}

	/**
	 *  Return a string containing a self-closing HTML element.
	 *
	 * @since 20170506
	 * @param  string $tag    Tag for the HTML element.
	 * @param  array  $attrs  Attributes to be applied to element.
	 * @return string         An HTML tag element in string form.
	 */
	public function get_tag( $tag, $attrs ) {
		$tag   = $this->sanitize_tag( $tag );
		$attrs = $this->filter_attributes_by_tag( $tag, $attrs );
		$html  = "<$tag" . $this->get_apply_attrs( $attrs );
		$html .= ( $this->is_tag_self_closing( $tag ) ) ? ' />' : '>';
		return $html;
	}


	/**
	 *  Echo the generated HTML attributes.
	 *
	 * @since 20170506
	 * @param array $attrs  An associative array containing the attribute keys and values.
	 */
	public function apply_attrs( $attrs ) {
		echo $this->get_apply_attrs( $attrs );
	}

	/**
	 *  Generates the HTML for the tag attributes
	 *
	 * @since 20170506
	 * @param  array  $attrs  Contains attribute/value pairs.
	 * @return string         Generated HTML attributes.
	 * @link https://konstantin.blog/2012/esc_url-vs-esc_url_raw/
	 */
	public function get_apply_attrs( $attrs ) {
		//  Array for attributes that do not require a value.
		static $is_allowed_no_value = null;
		if ( empty( $is_allowed_no_value ) ) {
			$is_allowed_no_value = array(
				'itemscope',
				'multiple',
				'novalidate',
				'required',
				'sandbox',
				'value',
			);
			$is_allowed_no_value = apply_filters( 'fluid_attr_is_allowed_no_value', $is_allowed_no_value );
		}
		//  Check if nonce is needed
		if ( ! empty( static::$attr_javascript_nonce ) ) {
			$attrs = $this->attr_nonce_check( $attrs );
		}
		//  Process attributes loop.
		$html = '';
		foreach( $attrs as $key => $value ) {
			$attr = sanitize_key( $key );
			//  empty returns true if string is '0', which is not a valid result in this case.
			if ( empty( $value ) && ( ! is_string( $value ) || ! mb_strlen( $value ) ) ) {
				if ( in_array( $attr, $is_allowed_no_value, true ) ) {
					$html .= ' ' . $attr;
				}
				continue;
			}
			switch( $attr ) {
				case 'action':
				case 'href':
				case 'itemtype': // schema.org
				case 'src':
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
				case 'onblur':
				case 'onchange':
				case 'onclick':
				case 'onfocus':
				case 'onkeydown':
				case 'onkeyup':
					$value = esc_js( $value );
					break;
				default:
					$value = esc_attr( $value );
			}
			$html .= ' ' . $attr . '="' . $value . '"';
		}
		return $html;
	}

	/**
	 *  Check to see if a nonce is required.
	 *
	 * @since 20200313
	 * @param  array $attrs  Attributes for HTML tag.
	 * @return array         Attributes with the nonce possibly added.
	 */
	private function attr_nonce_check( $attrs ) {
		static $nonce_required = array();
		if ( empty( $nonce_required ) ) {
			//  List of javascript DOM events
			$nonce_required = array(
				'onblur',
				'onchange',
				'onclick',
				'onfocus',
				'onkeydown',
				'onkeyup',
			);
			$nonce_required = apply_filters( 'element_attribute_nonce_required', $nonce_required );
		}
		if ( ! array_key_exists( 'nonce', $attrs ) ) {
			$keys = array_keys( $attrs );
			if ( array_intersect( $keys, $nonce_required ) ) {
				$attrs['nonce'] = static::$attr_javascript_nonce;
			}
		}
		return $attrs;
	}

	/**
	 *  Applys the wordpress function sanitize_html_class to a string containing multiple CSS classes.
	 *
	 * @since 20170510
	 * @param  string|array $classes  CSS classes to be sanitized.
	 * @return string                 Sanitized CSS classes.
	 */
	public function sanitize_html_class( $classes ) {
		if ( is_array( $classes ) ) {
			//  Pack it down then blow it up - insure each item is a single css class.
			$classes = explode( ' ', implode( ' ', $classes ) );
		} else {
			//  Convert string to an array.
			$classes = explode( ' ', $classes );
		}
		return implode( ' ', array_map( 'sanitize_html_class', array_unique( $classes ) ) );
	}

	/**
	 *  Sanitize the element tag, only allows numbers and lower case letters in the tag.  Don't need the numbers I think, but...
	 *
	 * @since 20180829
	 * @param  string $tag  Tag for the HTML element.
	 * @return string       The sanitized tag.
	 */
	protected function sanitize_tag( $tag ) {
		$tag = strtolower( $tag );
		$tag = preg_replace( '/[^a-z0-9]/', '', $tag );
		return $tag;
	}

	/**
	 *  Checks for tags that are self closing.
	 *
	 * @since 20170507
	 * @param  string $tag  Tag for the HTML element.
	 * @return bool         Is the passed tag self-closing?
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
	 *  Filter the attribute array by the HTML tag and the attribute.
	 *
	 * @since 20180425
	 * @param string $tag    Tag for the HTML element.
	 * @param array  $attrs  Attributes to be applied to element.
	 * @return array
	 * @link https://www.hongkiat.com/blog/wordpress-rel-noopener/
	 * @link https://support.performancefoundry.com/article/186-noopener-noreferrer-on-my-links
	 */
	public function filter_attributes_by_tag( $tag, $attrs ) {
		switch( $tag ) {
			case 'a':
				if ( array_key_exists( 'target', $attrs ) ) {
					$attrs['rel'] = ( ( array_key_exists( 'rel', $attrs ) ) ? $attrs['rel'] : '' ) . ' nofollow noopener noreferrer ugc';
				}
				break;
			case 'iframe':
				if ( static::$attr_iframe_sandbox ) {
					if ( ! array_key_exists( 'sandbox', $attrs ) ) {
						$attrs['sandbox'] = '';
					}
				}
				if ( array_key_exists( 'height', $attrs ) && array_key_exists( 'width', $attrs ) && ( ! array_key_exists( 'loading', $attrs ) ) ) {
					$attrs['loading'] = 'lazy';
				}
				break;
			case 'input':
				if ( apply_filters( 'fluid_filter_input_attributes', true, $attrs ) ) {
					if ( array_key_exists( 'type', $attrs ) ) {
						//  Effects keyboard shown on mobile platforms
						if ( in_array( $attrs['type'], [ 'number' ] ) && ! array_key_exists( 'step', $attrs ) ) {
							$attrs['type'] = 'text';
							$attrs['inputmode'] = 'decimal';
						}
						//  iOS Safari
						if ( in_array( $attrs['type'], [ 'tel' ] ) && ! array_key_exists( 'autocomplete', $attrs ) ) {
							$attrs['autocomplete'] = 'tel';
						}
					}
				}
				break;
			case 'script':
				if ( static::$attr_javascript_nonce && ! array_key_exists( 'nonce', $attrs ) ) {
					$attrs['nonce'] = static::$attr_javascript_nonce;
				}
				break;
			default:
		}
		// return apply_filters( 'fluid_filter_attributes_by_tag', $attrs, $tag );
		return $attrs;
	}


	/***   helper functions   ***/

	/**
	 *  Add the checked attribute to the attributes array.
	 *
	 * @since 20180424
	 * @param array $attrs    Accepted as reference.
	 * @param mixed $checked  value to check
	 * @param mixed $current  base value to check against
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 */
	public function checked( &$attrs, $checked, $current = true ) {
		$this->checked_selected_helper( $attrs, $checked, $current, 'checked' );
	}

	/**
	 *  Add the disabled attribute to the attributes array.
	 *
	 * @since 20180424
	 * @param array $attrs     Accepted as reference.
	 * @param mixed $disabled  Value to check.
	 * @param mixed $current   Base value to check against.
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 */
	public function disabled( &$attrs, $disabled, $current = true ) {
		$this->checked_selected_helper( $attrs, $disabled, $current, 'disabled' );
	}

	/**
	 *  Add the readonly attribute to the attributes array.
	 *
	 * @since 20180424
	 * @param array $attrs     Accepted as reference.
	 * @param mixed $readonly  Value to check.
	 * @param mixed $current   Base value to check against.
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 */
	public function readonly( &$attrs, $readonly, $current = true ) {
		$this->checked_selected_helper( $attrs, $readonly, $current, 'readonly' );
	}

	/**
	 *  Add the selected attribute to the attributes array.
	 *
	 * @since 20180424
	 * @param array $attrs     Accepted as reference.
	 * @param mixed $selected  Value to check.
	 * @param mixed $current   Base value to check against.
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 */
	public function selected( &$attrs, $selected, $current = true ) {
		$this->checked_selected_helper( $attrs, $selected, $current, 'selected' );
	}

	/**
	 *  Add the selected attribute on a multiple select input.
	 *
	 * @since 20200415
	 * @param array $attrs     Accepted as reference.
	 * @param mixed $selected  Value to check.
	 * @param mixed $current   Base value to check against.
	 */
	public function selected_m( &$attrs, $selected, $current = array() ) {
		foreach( $current as $value ) {
			$this->checked_selected_helper( $attrs, $selected, $value, 'selected' );
		}
	}

	/**
	 *  Workhorse of the checked, disabled, readonly, and selected methods.
	 *
	 * @since 20180424
	 * @param array  $attrs    Accepted as reference.
	 * @param mixed  $checked  Value to check.
	 * @param mixed  $current  Base value to check against.
	 * @param string $type     Attribute to add.
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 */
	protected function checked_selected_helper( &$attrs, $helper, $current, $type ) {
		if ( (string) $helper === (string) $current ) {
			$attrs[ $type ] = $type;
		}
	}

	/**
	 *  Add attributes for Personal Identifiable Information input fields
	 *
	 * @since 20191213
	 * @param  array $attrs  Element/tag attributes.
	 * @return array         Revised atributes.
	 */
	public function add_pii_attributes( $attrs = array() ) {
		$defaults = array(
			'autocapitalize' => 'off',
			'autocomplete'   => 'off',
			'autocorrect'    => 'off',
			'spellcheck'     => 'false',
		);
		return array_merge( $defaults, $attrs );
	}


	/***  methods for controlling the attr_iframe_sandbox property  ***/

	/**
	 *  Get the attr_iframe_sandbox property
	 *
	 * @since 20191213
	 * @return bool  Iframe sandbox setting.
	 */
	public function get_attr_iframe_sandbox() {
		return static::$attr_iframe_sandbox;
	}

	/**
	 *  Set the attr_iframe_sandbox property
	 *
	 * @since 20191213
	 * @param  bool  New seeting for iframe sandbox.
	 * @return bool  Current iframe sandbox setting.
	 */
	public function set_attr_iframe_sandbox( $new = true ) {
		static::$attr_iframe_sandbox = ( $new ) ? true : false;
		return static::$attr_iframe_sandbox;
	}

	/**
	 *  Set the nonce value.  It's validity is entirely your own responsibility.
	 *
	 * @since 20200313
	 * @param  string $nonce  New value to set.
	 * @return string         Current value of the nonce.
	 */
	public function set_attr_javascript_nonce( $nonce = '' ) {
		if ( empty( static::$attr_javascript_nonce ) ) {
			if ( $nonce && is_string( $nonce ) ) {
				static::$attr_javascript_nonce = $nonce;
			}
		}
		return static::$attr_javascript_nonce;
	}


}
