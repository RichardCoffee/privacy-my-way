<?php

/*
 *  https://secure.php.net/manual/en/language.oop5.magic.php
 *  https://lornajane.net/posts/2012/9-magic-methods-in-php
 */

trait PMW_Trait_Singleton {

	protected static $abort_construct;
	private   static $instance;

	public static function instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			$instance = new self();
			if ( ! static::$abort_construct ) {
				self::$instance = $instance;
			}
		}
		return self::$instance;
	}

	public static function get_instance( $args = array() ) {
		if ( ! ( self::$instance instanceof self ) ) {
			$instance = new self( $args );
			if ( ! static::$abort_construct ) {
				self::$instance = $instance;
			}
		}
		return self::$instance;
	}

	public function __clone() {
		$message = __( 'This class can not be cloned.' , 'tcc-privacy' ) . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}

	public function __sleep() {
		$message = __( 'This class can not be serialized.' , 'tcc-privacy' ) . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}

	public function __wakeup() {
		$message = __( 'This class can not be unserialized.' , 'tcc-privacy' ) . ' * ' . debug_calling_function();
		$version = ( isset( $this->version ) ) ? $this->version : '0.0.0';
		_doing_it_wrong( __FUNCTION__, esc_html( $message ), esc_html( $version ) );
	}


}
