<?php

class PMW_Plugin_Library {

	use PMW_Trait_Attributes;
	use PMW_Trait_Logging;
	use PMW_Trait_Magic;

	public function __construct() {
		$this->initialize();
		$this->logging_check_function();
	}

	protected function initialize() {
		$this->register__call( array( $this, 'logging_calling_location' ),          'debug_calling_function' );
		$this->register__call( array( $this, 'logging_get_calling_function_name' ), 'get_calling_function' );
		$this->register__call( array( $this, 'logging_was_called_by' ),             'was_called_by' );
		if ( WP_DEBUG && function_exists( 'add_action' ) ) {
			add_action( 'deprecated_function_run',    array( $this, 'logging_log_deprecated' ), 10, 3 );
			add_action( 'deprecated_constructor_run', array( $this, 'logging_log_deprecated' ), 10, 3 );
			add_action( 'deprecated_file_included',   array( $this, 'logging_log_deprecated' ), 10, 4 );
			add_action( 'deprecated_argument_run',    array( $this, 'logging_log_deprecated' ), 10, 3 );
			add_action( 'deprecated_hook_run',        array( $this, 'logging_log_deprecated' ), 10, 4 );
			add_action( 'doing_it_wrong_run',         array( $this, 'logging_log_deprecated' ), 10, 3 );
		}
	}


}
