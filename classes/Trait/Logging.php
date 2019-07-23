<?php
/**
 * @package Privacy_My_Way
 * @subpackage Traits
 * @requires PHP 5.3.6
 */

defined( 'WP_DEBUG' ) || exit;

trait PMW_Trait_Logging {

	public $logging_debug  =  WP_DEBUG; #  boolean  - enable/disable logging
	public $logging_force  =  false;    #  boolean  - can be used to force a single log entry
	public $logging_func;               #  callable - logging function: must be able to accept a variable number of parameters
	public $logging_prefix = 'rtc';     #  string   - log file prefix


/***   Action functions   ***/

	public function log() {
		call_user_func_array( [ $this, 'logging_entry' ], func_get_args() );
	}

# * @used-by PMW_Form_Admin::get_defaults()
	public function logg() {
		if ( is_callable( $this->logging_func ) && ( $this->logging_debug || $this->logging_force ) ) {
			call_user_func_array( $this->logging_func, func_get_args() );
			$this->logging_force = false;
		}
	}

	public function logobj( $object ) {
		$reduced = $this->logging_reduce_object( $object );
		call_user_func( [ $this, 'logging_entry' ], $reduced );
	}


/*** Discover functions   ***/

	/**
	 * Get the calling function.
	 *
	 * Retrieve information from the calling function/file, while also
	 * selectively skipping parts of the stack.
	 *
	 * @link http://php.net/debug_backtrace
	 * @param numeric $depth
	 * @return string
	 */
	public function logging_calling_location( $depth = 1 ) {
		#	This is not intended to be an exhaustive list
		static $skip_list = array(
#			'__call',
			'apply_filters',
			'call_user_func',
			'call_user_func_array',
#			'debug_calling_function',
#			'get_calling_function',
			'log',
			'logg',
#			'logging',
			'logging_log_deprecated',
			'logobj'
		);
		$default = $file = $func = $line = 'n/a';
		$call_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$total_cnt  = count( $call_trace );
		do {
			$file = ( isset( $call_trace[ $depth ]['file'] ) )     ? $call_trace[ $depth ]['file']     : $default;
			$line = ( isset( $call_trace[ $depth ]['line'] ) )     ? $call_trace[ $depth ]['line']     : $default;
			$depth++;
			$func = ( isset( $call_trace[ $depth ]['function'] ) ) ? $call_trace[ $depth ]['function'] : $default;
		} while( in_array( $func, $skip_list, true ) && ( $total_cnt > $depth ) );
		return "$file, $func, $line : $total_cnt/$depth";
	}

	# generally only called in library classes
	protected function logging_check_function() {
		if ( ! is_callable( $this->logging_func ) ) {
			$this->logging_func = array( $this, 'log' );
		}
	}

	public function logging_get_calling_function_name( $depth = 4 ) {
		$result = $this->logging_calling_location( max( $depth, 4 ) );
		$trace  = array_map( 'trim', explode( '/', $result ) );
		$result = $this->logging_calling_location( $trace[1] );
		$trace  = array_map( 'trim', explode( ',', $result ) );
		return $trace[1];
	}

	/**
	 * locates a function name in the stack
	 *
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

	# see classes/Plugin/Library.php or classes/Theme/Library.php for usage
	public function logging_log_deprecated() {
		$args = func_get_args();
		$this->log( $args, 'stack' );
	}


/***  Task functions   ***/

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

	protected function logging_write_destination( $log_file = 'error_log' ) {
		$destination = $log_file;
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$destination = WP_CONTENT_DIR . '/debug.log';
		} else if ( is_file( $destination ) && is_writable( $destination ) ) {
			// accept as is
		} else if ( is_dir( '../logs' ) && is_writable( '../logs' ) ) {
			$destination = '../logs/' . $this->logging_prefix . '-' . date( 'Ymd' ) . '.log';
#		} else {
#			$destination = 'error_log';
		}
		return $destination; // apply_filters( 'logging_write_destination', $destination );
	}

	public function logging_write_entry( $log_me, $log_file = 'error_log' ) {
		static $destination = '';
		if ( empty( $destination ) ) {
			$destination = $this->logging_write_destination( $log_file );
		}
		$message = $log_me;
		if ( is_array( $log_me ) || is_object( $log_me ) ) {
			$message = print_r( $log_me, true ); // PHP Fatal error:  Allowed memory size of 268435456 bytes exhausted (tried to allocate 33226752 bytes)
		} else if ( $log_me === 'stack' ) {
			$message = print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true );
		} else if ( $log_me === 'full-stack' ) {
			$message = print_r( debug_backtrace(), true );
		}
		$message = date( '[d-M-Y H:i:s e] ' ) . $message . "\n";
		error_log( $message, 3, $destination );
	}

/***   Helper functions   ***/

	public function logging_reduce_object( $object ) {
		$reduced = array();
		foreach ( (array)$object as $key => $value ) {
			if ( is_object( $value ) ) {
				$reduced[ $key ] = 'object ' . get_class( $value );
			} else {
				if ( is_array( $value ) && is_callable( $value ) && is_object( $value[0] ) ) {
					$value[0] = 'object ' . get_class( $value[0] );
				}
				$reduced[ $key ] = $value;
			}
		}
		return $reduced;
	}


}
