<?php
/*
 *  Provides methods for implementing singleton classes.
 *  Notes:  Any class that uses this trait must be sterile, or the child must declare 'private static $instance;'
 *          __clone and __wakeup are private, so can never get called.
 *
 * @package Privacy_My_Way
 * @subpackage Traits
 * @since 20170111
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017-2020, Richard Coffee
 * @link https://secure.php.net/manual/en/language.oop5.magic.php
 * @link https://lornajane.net/posts/2012/9-magic-methods-in-php
 * @link http://stackoverflow.com/questions/203336/creating-the-singleton-design-pattern-in-php
 */
defined( 'ABSPATH' ) || exit;


trait PMW_Trait_Singleton {


	/**
	 * @since 20170323
	 * @var bool  Flag to indicate that construction has been aborted.
	 */
	public  static $abort__construct = false;
	/**
	 * @since 20170111
	 * @var object  Pointer to the class instantiation.
	 */
	private static $instance = null;


	/**
	 *  Returns the class object.
	 *
	 * @since 20170202
	 * @return object  Pointer to the class instantiation.
	 */
	public static function instance() {
		return self::get_instance();
	}

	/**
	 *  Returns the class object.
	 *
	 * @since 20170111
	 * @param array $args  Passed to the class constructor method.
	 * @return object      Pointer to the class instantiation.
	 */
	public static function get_instance( $args = array() ) {
		if ( ! ( self::$instance instanceof self ) ) {
			$instance = new self( $args );
			if ( static::$abort__construct ) {
				static::$abort__construct = false;
			} else {
				self::$instance = $instance;
			}
		}
		return self::$instance;
	} //*/

	/**  An alternate methodology  **/
/*
	private static $instances = array();

	public static function get_instance( $args = array() ) {
		$class = get_called_class();
		if ( ! array_key_exists( $class, self::$instances ) ) {
			$instance = new $class( $args );
			if ( static::$abort__construct ) {
				static::$abort__construct = false;
			} else {
				self::$instances[ $class ] = $instance;
			}
		}
		return ( array_key_exists( $class, self::$instances ) ) ? self::$instances[ $class ] : null;
	} //*/

	/**
	 *  Blocks cloning of the class.
	 *
	 * @since 20170124
	 */
	private function __clone() {
		$message = __( 'This class can not be cloned.' , 'privacy-my-way' ) . ' * ' . debug_calling_function();
		$version = ( property_exists( $this, 'version' ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}

	/**
	 *  No sleep for you baby!
	 *
	 * @since 20170205
	 */
	public function __sleep() {
		$message = __( 'This class can not be serialized.' , 'privacy-my-way' ) . ' * ' . debug_calling_function();
		$version = ( property_exists( $this, 'version' ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}

	/**
	 *  No wakeup calls at this hotel.
	 *
	 * @since 20170205
	 */
	private function __wakeup() {
		$message = __( 'This class can not be unserialized.' , 'privacy-my-way' ) . ' * ' . debug_calling_function();
		$version = ( property_exists( $this, 'version' ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}


}
