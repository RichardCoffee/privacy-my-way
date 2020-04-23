<?php
/**
 *  Provides logging methods.
 *
 * @package Privacy_My_Way
 * @subpackage Traits
 * @requires PHP 5.3.6
 * @since 20170325
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017-2020, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Logging.php
 */
defined( 'WP_DEBUG' ) || exit;


trait PMW_Trait_Logging {


	/**
	 * @since 20170325
	 * @var bool  Enable/disable logging.
	 */
	public $logging_debug = WP_DEBUG;
	/**
	 * @since 20200228
	 * @var string  Used in conjunction with property 'logging_prefix'.
	 */
	public $logging_dir = '../logs';
	/**
	 * @since 20170325
	 * @var bool  Force logging for a single entry, by-passing the $logging_debug property.
	 */
	public $logging_force = false;
	/**
	 * @since 20170325
	 * @var string|array  Callable logging function, must be able to accept a variable number of parameters
	 */
	public $logging_func;
	/**
	 * @since 20180317
	 * @var string  Log file prefix, only used under certain circumstances, see method 'logging_write_destination' for usages.
	 */
	public $logging_prefix = 'rtc';


	/**  Logging functions  **/

	/**
	 *  Normal logging function, using internal logging methods.
	 *
	 * @since 20170529
	 * @param mixed  Multiple parameters are accepted by this function.
	 */
	public function log() {
		call_user_func_array( [ $this, 'logging_entry' ], func_get_args() );
	}

	/**
	 *  Logging function for when using an external logging function, or for when force a log entry.
	 *
	 * @since 20170325
	 * @used-by PMW_Form_Admin::get_defaults()
	 * @param mixed  Multiple parameters are accepted by this function.
	 */
	public function logg() {
		$this->logging_check_function();
		if ( $this->logging_debug || $this->logging_force ) {
			call_user_func_array( $this->logging_func, func_get_args() );
			$this->logging_force = false;
		}
	}

	/**
	 *  Callable wrapper for logging_reduce_object method.
	 *
	 * @since 20180501
	 * @param object $object
	 */
	public function logobj( $object ) {
		$reduced = $this->logging_reduce_object( $object );
		call_user_func( [ $this, 'logging_entry' ], $reduced );
	}


	/**  Discover functions  **/

	/**
	 * Get the calling function.
	 *
	 * Retrieve information from the calling function/file, while also
	 * selectively skipping parts of the stack.
	 *
	 * @since 20170529
	 * @link http://php.net/debug_backtrace
	 * @param integer $depth
	 * @return string
	 */
	public function logging_calling_location( $depth = 1 ) {
		#  This is not intended to be an exhaustive list
		static $skip_list = array(
			'__call',
			'apply_filters',
			'call_user_func',
			'call_user_func_array',
#			'debug_calling_function',
#			'get_calling_function',
			'log',
			'logg',
#			'logging',
			'logging_log_deprecated',
			'logobj',
		);
		$default = $file = $func = $line = 'n/a';
		$call_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$total_cnt  = count( $call_trace );
		do {
			$file = ( array_key_exists( 'file', $call_trace[ $depth ] ) )     ? $call_trace[ $depth ]['file']     : $default;
			$line = ( array_key_exists( 'line', $call_trace[ $depth ] ) )     ? $call_trace[ $depth ]['line']     : $default;
			if ( ! array_key_exists( ++$depth, $call_trace ) ) break;
			$func = ( array_key_exists( 'function', $call_trace[ $depth ] ) ) ? $call_trace[ $depth ]['function'] : $default;
		} while( in_array( $func, $skip_list ) );
		return "$file, $func, $line : $total_cnt/$depth";
	}

	/**
	 *  Checks to make sure the registered logging function is callable.
	 *
	 * @since 20170325
	 */
	protected function logging_check_function() {
		if ( ! is_callable( $this->logging_func ) ) {
			$this->logging_func = array( $this, 'log' );
		}
	}

	/**
	 *  Determines the name of the function which called the function from where this function was called.
	 *
	 * @since 20180410
	 * @param int starting depth for stack search
	 * @return string function name
	 */
	public function logging_get_calling_function_name( $depth = 1 ) {
		$result = $this->logging_calling_location( max( $depth, 1 ) );
		$trace  = array_map( 'trim', explode( '/', $result ) );
		$result = $this->logging_calling_location( end( $trace ) );
		$trace  = array_map( 'trim', explode( ',', $result ) );
		return $trace[1];
	}

	/**
	 *  Locates a function name in the stack
	 *
	 * @since 20180410
	 * @param string $func
	 * @return bool|numeric false or stack level
	 */
	public function logging_was_called_by( $func ) {
		$call_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		foreach( $call_trace as $key => $current ) {
			if ( ! empty( $current['function'] ) ) {
				if ( $current['function'] === $func ) {
					return $key;
				}
			}
		}
		return false;
	}

	/**
	 *  see classes/Plugin/Library.php or classes/Theme/Library.php for usage examples.
	 *
	 * @since 20180410
	 */
	public function logging_log_deprecated() {
		$args = func_get_args();
		$this->log( $args, 'stack' );
	}


	/**  Task functions  **/

	/**
	 *  Handles logging for multiple parameters.
	 *
	 * @since 20170529
	 */
	public function logging_entry() {
		if ( ( ! $this->logging_force ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) { return; }
		if ( $this->logging_debug || $this->logging_force ) {
			$args  = func_get_args();
			if ( $args ) {
				$depth = 1;
				if ( $args && is_int( $args[0] ) ) {
					$depth = array_shift( $args );
				}
				if ( $depth ) {
					$this->logging_write_entry( 'source:  ' . $this->logging_calling_location( $depth ) );
				}
				foreach( $args as $message ) {
					$this->logging_write_entry( $message );
				}
				$this->logging_force = false;
			}
		}
	}

	/**
	 *  Check for safe log file destinations.
	 *
	 * @since 20170529
	 * @param string  Log file name, not applicable if using wordpress
	 */
	protected function logging_write_destination( $log_file = 'error_log' ) {
		$destination = $log_file;
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$destination = WP_CONTENT_DIR . '/debug.log';
		} else if ( is_file( $destination ) && is_writable( $destination ) ) {
			// accept as is
		} else if ( is_dir( $this->logging_dir ) && is_writable( $this->logging_dir ) ) {
			$destination = $this->logging_dir . '/' . $this->logging_prefix . '-' . date( 'Ymd' ) . '.log';
		}
		if ( function_exists( 'apply_filters' ) ) return apply_filters( 'logging_write_destination', $destination );
		return $destination;
	}

	/**
	 *  Write the message out to the log file
	 *
	 * @since 20170529
	 * @param mixed data to write to log file
	 * @param string name of log file
	 */
	public function logging_write_entry( $log_me, $log_file = 'error_log' ) {
		static $destination = '';
		if ( empty( $destination ) ) $destination = $this->logging_write_destination( $log_file );
		$message = $log_me;
		if ( is_array( $log_me ) || is_object( $log_me ) ) {
			$message = print_r( $log_me, true );
		} else if ( in_array( $log_me, [ 'stack' ] ) ) {
			$backtrace = $this->logging_stack_with_origin( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) );
			$message = print_r( $backtrace, true );
		} else if ( in_array( $log_me, [ 'full-stack' ] ) ) {
			$message = print_r( debug_backtrace(), true );
		}
		$message = date( '[d-M-Y H:i:s e] ' ) . $message . "\n";
		if ( is_writable( $destination ) || is_writable( dirname( $destination ) ) ) {
			error_log( $message, 3, $destination );
		}
	}

	/**
	 *  Adds the line number of the calling function to the 'function' string
	 *
	 * @since 20200116
	 * @param array the debug backtrace array
	 * @return array the modified array
	 */
	private function logging_stack_with_origin( $backtrace ) {
		$current = $backtrace[0];
		foreach( $backtrace as $key => $data ) {
			if ( in_array( $key, [ 0 ] ) ) continue;
			if ( array_key_exists( 'line', $current ) ) {
				$backtrace[ $key ]['function'] .= " - {$current['line']}";
			}
			$current = $data;
		}
		return $backtrace;
	}

	/**  Helper functions  **/

	/**
	 *  Remove object references on an object, object is returned as an array.  Non-recursive.
	 *
	 * @since 20180501
	 * @param object $object
	 * @return array
	 */
	public function logging_reduce_object( $object ) {
		if ( ! is_object( $object ) ) return $object;
		$classes = array( get_class( $object ) );
		$parents = class_parents( $object, false );
		if ( $parents ) $classes = array_merge( $classes, $parents );
		$reduced = array( 'class:name' => $classes[0] );
		foreach ( (array) $object as $key => $value ) {
			if ( in_array( $key[0], [ "\0" ] ) ) {
				$key = str_replace( "\0*\0", 'protected:', $key );
				foreach( $classes as $class ) {
					$key = str_replace( "\0$class\0", "private:$class:", $key );
				}
			}
			if ( is_object( $value ) ) {
				$reduced[ $key ] = 'object ' . get_class( $value );
			} else if ( is_array( $value ) ) {
				foreach( $value as $vkey => $vvalue ) {
					if ( is_object( $vvalue ) ) {
						$value[ $vkey ] = 'object ' . get_class( $vvalue );
					}
				}
				$reduced[ $key ] = $value;
			} else {
				$reduced[ $key ] = $value;
			}
		}
		return $reduced;
	}


}
