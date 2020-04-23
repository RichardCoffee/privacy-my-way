<?php
/**
 *  Provides library access to core trait functions.
 *
 * @package Privacy_My_Way
 * @subpackage Plugin_Core
 * @since 20170503
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Plugin/Library.php
 */
defined( 'ABSPATH' ) || exit;

class PMW_Plugin_Library {

	/**
	 * @since 20180410
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Attributes.php
	 */
	use PMW_Trait_Attributes;
	/**
	 * @since 20180410
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Logging.php
	 */
	use PMW_Trait_Logging;
	/**
	 * @since 20180410
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Magic.php
	 */
	use PMW_Trait_Magic;

	/**
	 *  Constructor method.
	 *
	 * @since 20180410
	 */
	public function __construct() {
		$this->initialize();
		$this->logging_check_function();
	}

	/**
	 *  Performs certain setup tasks for the library.
	 *
	 * @since 20180410
	 */
	protected function initialize() {
		$this->register__call( [ $this, 'logging_calling_location' ],          'debug_calling_function' );
		$this->register__call( [ $this, 'logging_get_calling_function_name' ], 'get_calling_function' );
		$this->register__call( [ $this, 'logging_get_calling_function_name' ], 'get_calling' );
		$this->register__call( [ $this, 'logging_was_called_by' ],             'was_called_by' );
		$this->register__call( [ $this, 'logging_was_called_by' ],             'called_by' );
		if ( WP_DEBUG && function_exists( 'add_action' ) ) {
			add_action( 'deprecated_function_run',    [ $this, 'logging_log_deprecated' ], 10, 3 );
			add_action( 'deprecated_constructor_run', [ $this, 'logging_log_deprecated' ], 10, 3 );
			add_action( 'deprecated_file_included',   [ $this, 'logging_log_deprecated' ], 10, 4 );
			add_action( 'deprecated_argument_run',    [ $this, 'logging_log_deprecated' ], 10, 3 );
			add_action( 'deprecated_hook_run',        [ $this, 'logging_log_deprecated' ], 10, 4 );
			add_action( 'doing_it_wrong_run',         [ $this, 'logging_log_deprecated' ], 10, 3 );
		}
	}

	/**
	 *  Provides a default set of html attributes for use with the wordpress function kses().
	 *
	 * @since 20180501
	 */
	public function kses() {
		return array(
			'a'    => [ 'class' => [], 'href' => [], 'itemprop' => [], 'rel' => [], 'target' => [], 'title' => [], 'aria-label' => [] ],
			'b'    => [],
			'i'    => [ 'class' => [] ],
			'span' => [ 'class' => [], 'itemprop' => [] ],
		);
	}

	/**
	 *  Check for serialized data, and unserialize it.
	 *
	 * @since 20190730
	 * @param mixed $original
	 * @param array $acceptable  An array of class names that the unserialize function is allowed to create, PHP 7.0.0 or later only.
	 * @return mixed
	 */
	public function unserialize( $original, array $acceptable = array() ) {
		if ( is_string( $original ) ) {
			if ( $original === serialize( false ) ) return false;
			$test = @unserialize( $original, $acceptable );
			if ( $test ) return $test;
		}
		return $original;
	}


}
