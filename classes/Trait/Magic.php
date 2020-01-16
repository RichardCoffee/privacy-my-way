<?php
/**
 *  Trait to provide some basic magic methods
 *
 * @package Privacy_My_Way
 * @subpackage Traits
 * @since 20170116
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Magic.php
 * @link https://secure.php.net/manual/en/language.oop5.magic.php
 * @link http://php.net/manual/en/language.oop5.overloading.php
 * @link http://www.garfieldtech.com/blog/magical-php-call
 * @link https://lornajane.net/posts/2012/9-magic-methods-in-php
 */
trait PMW_Trait_Magic {


	/**
	 * @since 20170305
	 * @var bool toggles functionality of set method
	 */
	protected $set__callable = false;
	/**
	 * @since 20200114
	 * @var bool controls access to private variables
	 */
	protected $magic__private = true;
	/**
	 * @since 20170202
	 * @var array stores aliases for methods
	 */
	protected static $magic__call = array();


	/**
	 *  Enables aliasing of class methods - do not use is_callable() within this function
	 *
	 * @since 20170116
	 * @param string method name/alias
	 * @param mixed parameter(s) to be passed to method
	 * @return mixed
	 */
	public function __call( $string, $args ) {
		if ( array_key_exists( $string, static::$magic__call ) ) {
			return call_user_func_array( static::$magic__call[ $string ], $args );
		} else if ( in_array( $string, static::$magic__call, true ) ) {
			return call_user_func_array( $string, $args );
		} else if ( property_exists( $this, $string ) ) {
			return $this->$string;
		}
		$message = "non-callable function '$string'";
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$caller = @next( debug_backtrace() ); // without '@', this line produces "PHP Notice:  Only variables should be passed by reference"
			$message .= " called from {$caller['file']} on line {$caller['line']}";
		}
		trigger_error( $message, E_USER_ERROR );
	}

	/**
	 *  Default get method to allow read access to private/protected variables
	 *
	 * @since 20170116
	 * @param string property name
	 */
	public function __get( $name ) {
		if ( property_exists( $this, $name ) ) {
			if ( ! $this->magic__private ) {
				$test = new ReflectionProperty( $this, $name );
				if ( $test->isPrivate() ) return null;
			}
			return $this->$name;
		}
		return null;
	}

	/**
	 *  Default isset method to allow read access to private/protected variables
	 *
	 * @since 20170116
	 * @param string property name
	 */
	public function __isset( $name ) {
		return property_exists( $this, $name );
	} //*/

	/**
	 *  Add method aliases to static trait array
	 *
	 * @since 20170202
	 * @param string|array method name
	 * @param string method alias
	 * @return boolean
	 */
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

	/**
	 *  Provides ability to set generic private/protected properties
	 *
	 * @since 20170325
	 * @param string property name
	 * @param mixed new property value
	 * @return mixed old value of property
	 */
	public function set( $property, $value ) {
		$result = null;
		if ( $this->set__callable ) {
			if ( ( ! empty( $property ) ) && ( ! empty( $value ) ) ) {
				$result = "property '$property' does not exist.";
				if ( property_exists( $this, $property ) ) {
					$result = $this->$property;
					$this->{$property} = $value;
				}
			}
		}
		return $result;
	}


}
