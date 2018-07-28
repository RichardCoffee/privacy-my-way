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
		$this->register__call( [ $this, 'logging_calling_location' ],          'debug_calling_function' );
		$this->register__call( [ $this, 'logging_get_calling_function_name' ], 'get_calling_function' );
		$this->register__call( [ $this, 'logging_was_called_by' ],             'was_called_by' );
		if ( WP_DEBUG && function_exists( 'add_action' ) ) {
			add_action( 'deprecated_function_run',    [ $this, 'logging_log_deprecated' ], 10, 3 );
			add_action( 'deprecated_constructor_run', [ $this, 'logging_log_deprecated' ], 10, 3 );
			add_action( 'deprecated_file_included',   [ $this, 'logging_log_deprecated' ], 10, 4 );
			add_action( 'deprecated_argument_run',    [ $this, 'logging_log_deprecated' ], 10, 3 );
			add_action( 'deprecated_hook_run',        [ $this, 'logging_log_deprecated' ], 10, 4 );
			add_action( 'doing_it_wrong_run',         [ $this, 'logging_log_deprecated' ], 10, 3 );
		}
	}

	#duplicated in PMW_Theme_Library
	public function kses() {
		return array(
			'a'    => [ 'class' => [ ], 'href' => [ ], 'itemprop' => [ ], 'rel' => [ ], 'target' => [ ], 'title' => [ ], 'aria-label' => [ ] ],
			'i'    => [ 'class' => [ ] ],
			'span' => [ 'class' => [ ], 'itemprop' => [ ] ],
		);
	}


}
