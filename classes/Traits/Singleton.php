<?php

/*
 *  https://secure.php.net/manual/en/language.oop5.magic.php
 *  https://lornajane.net/posts/2012/9-magic-methods-in-php
 */

trait TCC_Trait_Singleton {

	private static $instance;

	public static function instance() {
		if ( ! (self::$instance instanceof self) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function get_instance( $args = array() ) {
		if ( ! (self::$instance instanceof self) ) {
			self::$instance = new self( $args );
		}
		return self::$instance;
	}

	public function __clone() {
		$message = __( 'This class can not be cloned.' , 'tcc-fluid') . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, $message, $version );
	}

	public function __sleep() {
		$message = __( 'This class can not be serialized.' , 'tcc-fluid') . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, $message, $version );
	}

	public function __wakeup() {
		$message = __( 'This class can not be unserialized.' , 'tcc-fluid') . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, $message, $version );
	}


}
