<?php

trait PMW_Trait_Logging {

	protected $logging_debug =  WP_DEBUG;    #  boolean - enable/disable logging
	protected $logging_force =  false;       #  boolean - for debugging, can be used to force a single log entry
	protected $logging_func  = 'log_entry';  #  string/array - logging function: must be able to accept a variable number of parameters


	public function log() {
		if ( $this->logging_debug || $this->logging_force ) {
			call_user_func_array( array( $this, 'logging_entry' ), func_get_args() );
		}
		$this->logging_force = false;
	}

	protected function logging() {
		if ( $this->logging_func && ( $this->logging_debug || $this->logging_force ) ) {
			call_user_func_array( $this->logging_func, func_get_args() );
		}
		$this->logging_force = false;
	}

	protected function logging_calling_function( $depth = 1 ) {
		#	This is not intended to be an exhaustive list
		static $skip_list = array(
			'apply_filters',
			'call_user_func',
			'call_user_func_array',
			'log',
			'logging',
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
		if ( is_string( $this->logging_func ) && function_exists( $this->logging_func ) ) {
			return;
		} else if ( is_array( $this->logging_func ) && method_exists( $this->logging_func[0], $this->logging_func[1] ) ) {
			return;
		}
		$this->logging_func = array( $this, 'logging_entry' );
	}

	protected function logging_entry() {
		if ( WP_DEBUG ) {
			$args  = func_get_args();
			if ( $args ) {
				$depth = 1;
				if ( $args && is_int( $args[0] ) ) {
					$depth = array_shift( $args );
				}
				if ( $depth ) {
					$this->logging_write_entry( 'source:  ' . $this->logging_calling_function( $depth ) );
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
			$destination = '../logs/pbl-' . date( 'Ymd' ) . '.log';
		} else if ( function_exists( 'pbl_raw_path' ) ) {
			$destination = pbl_raw_path() . '/error_log';
		}
		return $destination;
	}

	protected function logging_write_entry( $log_me, $log_file = 'error_log' ) {
		static $destination = '';
		if ( empty( $destination ) ) {
			$destination = $this->logging_write_destination( $log_file );
		}
		$message = $log_me;
		if ( is_array( $log_me ) || is_object( $log_me ) ) {
			$message = print_r( $log_me, true );
		} else if ( $log_me === 'stack' ) {
			$message = print_r( debug_backtrace(), true );
		}
		$message = date( '[d-M-Y H:i:s e] ' ) . $message . "\n";
		error_log( $message, 3, $destination );
	}


}
