<?php

/*
 *  https://secure.php.net/manual/en/language.oop5.magic.php
 *  https://lornajane.net/posts/2012/9-magic-methods-in-php
 *  http://stackoverflow.com/questions/203336/creating-the-singleton-design-pattern-in-php
 *
 *  Notes:  Any class that uses this trait must be sterile, or the child must declare 'private static $instance;'
 *          __clone, __sleep, and __wakeup are private, so can never get called.
 */

trait PMW_Trait_Singleton {


	public  static $abort__construct = false;
	private static $instance = null;


	public static function instance() {
		return self::get_instance();
	}

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
		if ( ! isset( self::$instances[ $class ] ) ) {
			$instance = new $class( $args );
			if ( static::$abort__construct ) {
				static::$abort__construct = false;
			} else {
				self::$instances[ $class ] = $instance;
			}
		}
		return ( isset( self::$instances[ $class ] ) ) ? self::$instances[ $class ] : null;
	} //*/

	private function __clone() {
		$message = __( 'This class can not be cloned.' , 'rtc-privacy' ) . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}

	public function __sleep() {
		$message = __( 'This class can not be serialized.' , 'rtc-privacy' ) . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}

	private function __wakeup() {
		$message = __( 'This class can not be unserialized.' , 'rtc-privacy' ) . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}


}
