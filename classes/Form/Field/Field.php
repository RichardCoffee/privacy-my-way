<?php
/**
 *  Generic abstract class for displaying input fields
 *
 *  Note:  The sanitize callback may be called twice, as per https://core.trac.wordpress.org/ticket/21989
 *
 * @package Privacy_My_Way
 * @subpackage Forms
 * @since 20170211
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Form/Field/Field.php
 */
abstract class PMW_Form_Field_Field {

	/**
	 *  Variables used as html attributes.
	 */
	/**
	 *  Element class attribute.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $class = '';
	/**
	 *  Element id attribute.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $id = '';
	/**
	 *  Element name attribute.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $name = '';
	/**
	 *  Element onchange attribute.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $onchange = null;
	/**
	 *  Element placeholder attribute.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $placeholder = '';
	/**
	 *  Element title attribute.
	 *
	 * @since 20170308
	 * @var string
	 */
	protected $title = '';
	/**
	 *  Element type attribute.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $type = 'text';
	/**
	 *  Element value attribute.
	 *
	 * @since 20170211
	 * @var string|mixed
	 */
	protected $value = '';
	/**
	 *  Internal class properties not associated with html attributes.
	 */
	/**
	 *  Flag to indicate that bootstrap css classes are to be used.
	 *
	 * @since 20180413
	 * @var boolean
	 */
	protected $bootstrap = true;
	/**
	 *  Value to be used as the default for the element.
	 *
	 * @since 20170211
	 * @var string|mixed
	 */
	protected $default = '';
	/**
	 *  Text used for the element label.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $description = '';
	/**
	 *  CSS classes for the element label.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $label_css = '';
	/**
	 *  Default sanitization method.
	 *
	 * @since 20170211
	 * @var string
	 */
	protected $sanitize = 'esc_html';
	/**
	 *  Whether or not to show the element label.
	 *
	 * @since 20180413
	 * @var boolean
	 */
	protected $see_label = true;
	/**
	 *  Default date format to be used for date fields.  This doe not belong here!
	 *
	 * @since 20180413
	 * @var string
	 */
	protected static $date_format = 'm/d/y';

	/**
	 *  Trait that provides functions to build the html for the element.
	 *
	 * @since 20180314
	 */
	use PMW_Trait_Attributes;
	/**
	 *  Trait that provides public read access to protected/private properties.
	 *
	 * @since 20170317
	 */
	use PMW_Trait_Magic;
	/**
	 *  Trait used to assign incoming values to class properties.
	 *
	 * @since 20170211
	 */
	use PMW_Trait_ParseArgs;


	/**
	 *  Class constructor method.
	 *
	 * @since 20170211
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
#		if ( empty( self::$date_format ) ) {
#			self::$date_format = get_option( 'date_format' );
#		}
		$args = $this->convert_args( $args );
		$this->parse_args( $args );
		if ( ( empty( $this->placeholder ) ) && ( ! empty( $this->description ) ) ) {
			$this->placeholder = $this->description;
		}
		if ( empty( $this->id ) ) {
			$this->id = $this->name;
		}
		if ( $this->bootstrap ) {
			$this->add_form_control_css();
		}
		$this->value = $this->sanitize( $this->value );
	}

	/**
	 *  Convert legacy arguments to new and improved property names.
	 *
	 * @since 20190726
	 * @param array $args
	 * @return array
	 */
	private function convert_args( $args ) {
		$check = array(
			'field_css'    => 'class',
			'field_help'   => 'title',
			'field_id'     => 'id',
			'field_name'   => 'name',
			'field_value'  => 'value',
			'form_control' => 'bootstrap'
		);
		foreach( $check as $old => $new ) {
			if ( isset( $args[ $old ] ) ) {
				$args[ $new ] = $args[ $old ];
			}
		}
		return $args;
	}

	/**
	 *  Getter for the static property date_format.
	 *
	 * @since 20180413
	 * @return string
	 */
	public function get_date_format() {
		return static::$date_format;
	}

	/**
	 *  Builds and displays the element.
	 *
	 * @since 20170211
	 */
	public function input() {
		$this->element( 'input', $this->get_input_attributes() );
	}

	/**
	 *  Builds and returns the element as a string.
	 *
	 * @since 20180413
	 * @return string
	 */
	public function get_input() {
		return $this->get_element( 'input', $this->get_input_attributes() );
	}

	/**
	 *  Creates the array that controls the element's behavior.
	 *
	 *  Please note that any empty value will not get built into the element.
	 *
	 * @since 20180426
	 * @return array
	 */
	protected function get_input_attributes() {
		$attrs = array(
			'id'          => $this->id,
			'type'        => $this->type,
			'class'       => $this->class,
			'name'        => $this->name,
			'value'       => $this->value,
			'placeholder' => $this->placeholder,
			'onchange'    => $this->onchange,
		);
		return $attrs;
	}

	/**
	 *  Builds and displays the element label.
	 *
	 *  Neither this method nor get_label() should be used when enclosing the element
	 *  within the label.  Use label_tag() or get_label_tag() instead.
	 *
	 * @since 20170211
	 */
	protected function label() {
		if ( empty( $this->description ) ) return;
		$this->element( 'label', $this->get_label_attributes(), $this->description );
	}

	/**
	 *  Builds and displays the label tag.  Calling code is responsible for closing the tag.
	 *
	 * @since 20190726
	 */
	protected function label_tag() {
		$this->tag( 'label', $this->get_label_attributes(), $this->description );
	}

	/**
	 *  Builds and returns the element's label as a string.
	 *
	 * @since 20180413
	 * @return string
	 */
	protected function get_label() {
		if ( empty( $this->description ) ) return '';
		return $this->get_element( 'label', $this->get_label_attributes(), $this->description );
	}

	protected function get_label_tag() {
		return $this->get_tag( 'label', $this->get_label_attributes(), $this->description );
	}

	/**
	 *  Assembles the array that controls the attributes for the element's label.
	 *
	 * @since 20180426
	 * @return array
	 */
	protected function get_label_attributes() {
		$srt = ( $this->bootstrap ) ? 'sr-only' : 'screen-reader-text';
		$attrs = array(
			'id'    => $this->id . '_label',
			'class' => $this->label_css . ( $this->see_label ) ? '' : " $srt",
			'for'   => $this->id,
		);
		return $attrs;
	}

	/**
	 *  Possibly adds the bootstrap css string for form controls.
	 *
	 * @since 20180413
	 * @param string $new
	 * @return string
	 */
	protected function add_form_control_css( $new = 'form-control' ) {
		$css = explode( ' ', $this->class );
		if ( ! in_array( $new, $css ) ) {
			$css[] = $new;
		}
		$this->class = implode( ' ', $css );
	}

	/**
	 *  Provides a default sanitization method
	 *
	 * @since 20170308
	 * @param string|mixed $input
	 * @return string|mixed
	 */
	public function sanitize( $input ) {
		if ( $this->sanitize && is_callable( $this->sanitize ) ) {
			$output = call_user_func( $this->sanitize, $input );
		} else {
			$output = wp_strip_all_tags( wp_unslash( $input ) );
		}
		return $output;
	}


}
