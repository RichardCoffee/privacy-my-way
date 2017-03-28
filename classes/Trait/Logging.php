<?php

trait PMW_Trait_Logging {

	protected $debug   =  WP_DEBUG;    #  boolean - enable/disable logging
	protected $force   =  false;       #  boolean - for debugging, can be used to force a single log entry
	protected $logging = 'pmw_log_entry';  #  string/array - logging function: must be able to accept a variable number of parameters


	protected function check_logging_option() {
		#| check logging option
		if ( is_string( $this->logging ) ) {
			if ( ! function_exists( $this->logging ) ) {
				$this->logging = $this->debug = false;
			}
		} else if ( is_array( $this->logging ) ) {
			if ( ! method_exists( $this->logging[0], $this->logging[1] ) ) {
				$this->logging = $this->debug = false;
			}
		} else {
			$this->logging = $this->debug = false;
		}
	}

	protected function logging() {
		if ( $this->logging && ( $this->debug || $this->force ) ) {
			call_user_func_array( $this->logging, func_get_args() );
		}
		$this->force = false;
	}

}
