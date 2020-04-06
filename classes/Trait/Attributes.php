<?php
/**
 *  A trait that provides methods to generate sanitized HTML elements.
 *
 * @package Privacy_My_Way
 * @subpackage Traits
 * @since 20170506
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
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
		$attrs = $this->filter_attributes_by_tag( $tag, $attrs );
		$html  = '<' . $this->sanitize_tag( $tag );
		$html .= $this->get_apply_attrs( $attrs );
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
	 * @link https://konstantin.blog/2012/esc_url-vs-esc_url_raw/
	 * @param  array  $attrs  Contains attribute/value pairs.
	 * @return string         Generated HTML attributes.
	 */
	public function get_apply_attrs( $attrs ) {
		//  Array for attributes that do not require a value.
/*		static $is_allowed_no_value = null;
		if ( empty( $is_allowed_no_value ) ) {
			$is_allowed_no_value = apply_filters( 'fluid_attr_is_allowed_no_value', [ 'itemscope', 'multiple', 'required', 'sandbox', 'value' ] );
		} //*/
		$is_allowed_no_value = array( 'itemscope', 'multiple', 'value', 'required', 'sandbox' );
		if ( ! empty( static::$attr_javascript_nonce ) ) {
			$attrs = $this->attr_nonce_check( $attrs );
		}
		//  Process attributes loop.
		$html = '';
		foreach( $attrs as $key => $value ) {
			$attr = sanitize_key( $key );
			if ( empty( $value ) ) {
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
			$nonce_required = apply_filters( 'fluid_attr_nonce_required', [ 'onchange', 'onclick' ] );
		}
		if ( ! array_key_exists( 'nonce', $attrs ) ) {
			foreach( $nonce_required as $required ) {
				if ( array_key_exists( $required, $attrs ) ) {
					$attrs['nonce'] = static::$attr_javascript_nonce;
					return $attrs;
				}
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
			//  Pack it down then blow it up - insure each item is a single class.
			$classes = explode( ' ', implode( ' ', $classes ) );
		} else {
			//  Convert string to an array.
			$classes = explode( ' ', $classes );
		}
		return implode( ' ', array_map( 'sanitize_html_class', array_unique( $classes ) ) );
	}

	/**
	 *  Sanitize the element tag.
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
	 *  Filter the attribute array by the HTML tag and the attribute.
	 *
	 * @since 20180425
	 * @link https://www.hongkiat.com/blog/wordpress-rel-noopener/
	 * @link https://support.performancefoundry.com/article/186-noopener-noreferrer-on-my-links
	 * @param string $tag    Tag for the HTML element.
	 * @param array  $attrs  Attributes to be applied to element.
	 * @return array
	 */
	public function filter_attributes_by_tag( $tag, $attrs ) {
		if ( in_array( $tag, [ 'a' ] ) && array_key_exists( 'target', $attrs ) ) {
			$attrs['rel'] = ( ( array_key_exists( 'rel', $attrs ) ) ? $attrs['rel'] : '' ) . ' nofollow noopener noreferrer ugc';
		}
		if ( in_array( $tag, [ 'iframe' ] ) && static::$attr_iframe_sandbox ) {
			if ( ! array_key_exists( 'sandbox', $attrs ) ) {
				$attrs['sandbox'] = '';
			}
		}
		if ( in_array( $tag, [ 'script' ] ) && ! empty( static::$attr_javascript_nonce ) ) {
			if ( ! array_key_exists( 'nonce', $attrs ) ) {
				$attrs['nonce'] = static::$attr_javascript_nonce;
			}
		}
		// return apply_filters( 'fluid_filter_attributes_by_tag', $attrs, $tag );
		return $attrs;
	}


	/***   helper functions   ***/

	/**
	 *  Add the checked attribute to the attributes array.
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs    Accepted as reference.
	 * @param mixed $checked  value to check
	 * @param mixed $current  base value to check against
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
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs     Accepted as reference.
	 * @param mixed $readonly  Value to check.
	 * @param mixed $current   Base value to check against.
	 */
	public function readonly( &$attrs, $readonly, $current = true ) {
		$this->checked_selected_helper( $attrs, $readonly, $current, 'readonly' );
	}

	/**
	 *  Add the selected attribute to the attributes array.
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array $attrs     Accepted as reference.
	 * @param mixed $selected  Value to check.
	 * @param mixed $current   Base value to check against.
	 */
	public function selected( &$attrs, $selected, $current = true ) {
		$this->checked_selected_helper( $attrs, $selected, $current, 'selected' );
	}

	/**
	 *  Workhorse of the checked, disabled, readonly, and selected methods.
	 *
	 * @since 20180424
	 * @link https://developer.wordpress.org/reference/files/wp-includes/general-template.php/
	 * @param array  $attrs    Accepted as reference.
	 * @param mixed  $checked  Value to check.
	 * @param mixed  $current  Base value to check against.
	 * @param string $type     Attribute to add.
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
	public function set_attr_javascript_nonce( $nonce  = '' ) {
		if ( empty( static::$attr_javascript_nonce ) ) {
			if ( $nonce && is_string( $nonce ) ) {
				static::$attr_javascript_nonce = $nonce;
			}
		}
		return static::$attr_javascript_nonce;
	}


}
