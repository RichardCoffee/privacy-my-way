<?php

trait PMW_Trait_Logging {

	protected $logging_debug  =  WP_DEBUG; #  boolean  - enable/disable logging
	public    $logging_force  =  false;    #  boolean  - can be used to force a single log entry
	protected $logging_func;               #  callable - logging function: must be able to accept a variable number of parameters
	protected $logging_prefix = 'rtc';     #  string   - log file prefix


/***   Action functions   ***/

	public function log() {
		call_user_func_array( array( $this, 'logging_entry' ), func_get_args() );
		$this->logging_force = false;
	}

	public function logg() {
		if ( is_callable( $this->logging_func ) && ( $this->logging_debug || $this->logging_force ) ) {
			call_user_func_array( $this->logging_func, func_get_args() );
		}
		$this->logging_force = false;
	}


/*** Discover functions   ***/

	protected function logging_calling_location( $depth = 1 ) {
		#	This is not intended to be an exhaustive list
		static $skip_list = array(
			'apply_filters',
			'call_user_func',
			'call_user_func_array',
			'log',
			'logg',
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
		return "$file, $func, $line";
	}

	protected function logging_check_function() {
		if ( ! is_callable( $this->logging_func ) ) {
			$this->logging_func = array( $this, 'log' );
		}
	}

	public function logging_get_calling_function_name( $depth = 1 ) {
		$result = logging_calling_location( $depth );
		$trace  = array_map( 'trim', explode( ',', $result ) );
		return $trace[1];
	}

	public function logging_was_called_by( $func ) {
		$call_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		foreach( $call_trace as $current ) {
			if ( ! empty( $current['function'] ) ) {
				if ( $current['function'] === $func ) {
					return true;
				}
			}
		}
		return false;
	}

	# see classes/Plugin/Library.php for usage
	public function logging_log_deprecated() {
		$args = func_get_args();
		$this->log( $args, 'stack' );
	}


/***  Task functions   ***/

	protected function logging_entry() {
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
			}
		}
	}

	protected function logging_write_destination( $log_file = 'error_log' ) {
		$destination = $log_file;
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$destination = WP_CONTENT_DIR . '/debug.log';
		} else if ( is_writable( '../logs' ) && ( is_dir( '../logs' ) ) ) {
			$destination = '../logs/' . $this->logging_prefix . '-' . date( 'Ymd' ) . '.log';
		} else {
			$destination = 'error_log';
		}
		return $destination; // apply_filters( 'logging_write_destination', $destination );
	}

	protected function logging_write_entry( $log_me, $log_file = 'error_log' ) {
		static $destination = '';
		if ( empty( $destination ) ) {
			$destination = $this->logging_write_destination( $log_file );
		}
		$message = $log_me;
		if ( is_array( $log_me ) || is_object( $log_me ) ) {
			$message = print_r( $log_me, true ); // PHP Fatal error:  Allowed memory size of 268435456 bytes exhausted (tried to allocate 33226752 bytes)
		} else if ( $log_me === 'stack' ) {
			$message = print_r( debug_backtrace(), true );
		}
		$message = date( '[d-M-Y H:i:s e] ' ) . $message . "\n";
		error_log( $message, 3, $destination );
	}


}
