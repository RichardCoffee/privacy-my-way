<?php
/**
 *
 */

if ( ! function_exists( 'debug_calling_function' ) ) {
	/**
	*	Get the calling function.
	*
	*	Retrieve information from the calling function/file, while also
	*	selectively skipping parts of the stack.
	*
	*	@package    Fluidity
	*	@subpackage Debugging
	*	@requires   PHP 5.3.6
	*/
	#	http://php.net/debug_backtrace
	function debug_calling_function( $depth = 1 ) {
		$default = $file = $func = $line = 'n/a';
		$call_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$total_cnt  = count( $call_trace );
		#	This is not meant to be an exhaustive list
		$skip_list  = array(
			'call_user_func',
			'call_user_func_array',
			'debug_calling_function',
			'logging'
		);
		do {
			$file = ( isset( $call_trace[ $depth ]['file']))       ? $call_trace[ $depth ]['file']     : $default;
			$line = ( isset( $call_trace[ $depth ]['line'] ) )     ? $call_trace[ $depth ]['line']     : $default;
			$depth++;
			$func = ( isset( $call_trace[ $depth ]['function'] ) ) ? $call_trace[ $depth ]['function'] : $default;
		} while( in_array( $func, $skip_list ) && ( $total_cnt > $depth ) );
		return "$file, $func, $line";
	}
}

if ( ! function_exists( 'get_calling_function' ) ) {
	function get_calling_function( $depth = 1 ) {
		$result = debug_calling_function( $depth );
		$trace  = array_map( 'trim', explode( ',', $result ) );
		return $trace[1];
	}
}

if ( ! function_exists( 'was_called_by' ) ) {
	function was_called_by( $func ) {
		$call_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		foreach( $call_trace as $current ) {
			if ( ! empty( $current['function'] ) ) {
				if ( $current['function'] === $func )
					return true;
			}
		}
		return false;
	}
}

#  generate log entry, with comment
if ( ! function_exists( 'log_entry' ) ) {
	function log_entry() {
		if ( WP_DEBUG ) {
			$args  = func_get_args();
			$depth = 1;
			if ( $args && is_int( $args[0] ) ) {
				$depth = array_shift( $args );
			}
			if ( $depth ) { error_log( 'source:  ' . debug_calling_function( $depth ) ); }
			foreach( $args as $message ) {
				#	log an array or object
				if ( is_array( $message ) || is_object( $message ) ) {
					error_log( print_r( $message, true ) );
				#	log the stack trace
				} else if ( $message === 'stack' ) {
					error_log( print_r( debug_backtrace(), true ) );
				#	log everything else
				} else {
					error_log( $message );
				}
			}
		}
	}
}

if ( WP_DEBUG && ! function_exists( 'tcc_log_deprecated' ) ) {
	function tcc_log_deprecated() {
		$args = func_get_args();
		log_entry( $args, 'stack' );
	}
	add_action( 'deprecated_function_run',    'tcc_log_deprecated', 10, 3 );
	add_action( 'deprecated_constructor_run', 'tcc_log_deprecated', 10, 3 );
	add_action( 'deprecated_file_included',   'tcc_log_deprecated', 10, 4 );
	add_action( 'deprecated_argument_run',    'tcc_log_deprecated', 10, 3 );
	add_action( 'deprecated_hook_run',        'tcc_log_deprecated', 10, 4 );
	add_action( 'doing_it_wrong_run',         'tcc_log_deprecated', 10, 3 );
}
