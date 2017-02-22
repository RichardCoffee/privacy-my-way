<?php

/*
 *  https://secure.php.net/manual/en/language.oop5.magic.php
 *  http://www.garfieldtech.com/blog/magical-php-call
 *  https://lornajane.net/posts/2012/9-magic-methods-in-php
 */

trait TCC_Trait_Magic {

	protected static $magic__call = array();

	public function __call( $string, $args ) {
		$return = false;
		if ( isset( self::$magic__call[ $string ] ) ) {
			$return = call_user_func_array( self::$magic__call[ $string ], $args );
		} else if ( in_array( $string, self::$magic__call, true ) ) {
			$return = call_user_func_array( $string, $args );
		} else if ( property_exists( $this, $string ) ) {
			$return = $this->$string;
		}
		if ( ! $return ) {
			log_entry( 'unknown method called:  ' . $string, 'dump' );
		}
		return $return;
	}

	public function __get($name) {
		if (property_exists($this,$name)) {
			return $this->$name; } #  Allow read access to private/protected variables
		return null;
	}

	public function __isset($name) {
		return isset($this->$name); #  Allow read access to private/protected variables
	} //*/

	public static function register__call( $method, $alias = false ) {
		if ( $alias ) {
			self::$magic__call[ $alias ] = $method;
		} else {
			self::$magic__call[] = $method;
		}
	} //*/

}
