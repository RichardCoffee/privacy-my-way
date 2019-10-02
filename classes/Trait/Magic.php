<?php

/*
 *  https://secure.php.net/manual/en/language.oop5.magic.php
 *  http://php.net/manual/en/language.oop5.overloading.php
 *  http://www.garfieldtech.com/blog/magical-php-call
 *  https://lornajane.net/posts/2012/9-magic-methods-in-php
 */

trait PMW_Trait_Magic {


	protected static $magic__call   = array();
	protected static $set__callable = false;


	# do not use is_callable() within this function
	public function __call( $string, $args ) {
		$return = "non-callable function '$string'";
		if ( array_key_exists( $string, static::$magic__call ) ) {
			$return = call_user_func_array( static::$magic__call[ $string ], $args );
		} else if ( in_array( $string, static::$magic__call, true ) ) {
			$return = call_user_func_array( $string, $args );
		} else if ( property_exists( $this, $string ) ) {
			$return = $this->$string;
		}
		return $return;
	}

	public function __get( $name ) {
		if ( property_exists( $this, $name ) ) {
			return $this->$name;  #  Allow read access to private/protected variables
		}
		return null;
	}

	public function __isset( $name ) {
		return isset( $this->$name ); #  Allow read access to private/protected variables
	} //*/

	public function register__call( $method, $alias = false ) {
		if ( is_callable( $method ) ) {
			if ( $alias ) {
				static::$magic__call[ $alias ] = $method;
			} else {
				$key = ( is_array( $method ) ) ? $method[1] : $method;
				static::$magic__call[ $key ] = $method;
			}
			return true;
		}
		return false;
	} //*/

	public function set( $property, $value ) {
		if ( static::$set__callable ) {
			if ( ( ! empty( $property ) ) && ( ! empty( $value ) ) ) {
				if ( property_exists( $this, $property ) ) {
					$this->{$property} = $value;
				}
			}
		}
	}


}
