<?php

trait PMW_Trait_Logging {

	protected $logging_debug =  WP_DEBUG;    #  boolean - enable/disable logging
	protected $logging_force =  false;       #  boolean - for debugging, can be used to force a single log entry
	protected $logging_func  = 'log_entry';  #  string/array - logging function: must be able to accept a variable number of parameters


	protected function check_logging_option() {
		#| check logging option
		if ( is_string( $this->logging_func ) ) {
			if ( ! function_exists( $this->logging_func ) ) {
				$this->logging_func = $this->logging_debug = false;
			}
		} else if ( is_array( $this->logging_func ) ) {
			if ( ! method_exists( $this->logging_func[0], $this->logging_func[1] ) ) {
				$this->logging_func = $this->logging_debug = false;
			}
		} else {
			$this->logging_func = $this->logging_debug = false;
		}
	}

	protected function logging() {
		if ( $this->logging_func && ( $this->logging_debug || $this->logging_force ) ) {
			call_user_func_array( $this->logging_func, func_get_args() );
		}
		$this->logging_force = false;
	}

}
