<?php

abstract class PMW_Options_Options {

	protected $base     = 'options';
	protected $priority = 1000;
	protected $screen   = array();

	abstract protected function form_title();
	abstract public    function describe_options();
	abstract protected function options_layout();

	public function __construct() {
		add_filter( 'fluidity_options_form_layout', array( $this, 'form_layout' ), $this->priority );
	}

	public function form_layout( $form ) {
		if ( ! isset( $form[ $this->base ] ) ) {
			$form[ $this->base ] = ( empty( $this->screen ) ) ? $this->default_form_layout() : $this->screen;
		}
		return $form;
	}

	public function default_form_layout() {
		$this->screen = array(
			'describe' => array( $this, 'describe_options' ),
			'title'    => $this->form_title(),
			'option'   => 'tcc_options_' . $this->base,
			'layout'   => $this->options_layout(),
		);
		return $this->screen;
	}

	public function get_default_options() {
		$form = $this->options_layout( true );
		$opts = array();
		foreach( $form as $key => $option ) {
			if ( isset( $option['default'] ) ) {
				$opts[ $key ] = $option['default'];
			}
		}
		return $opts;
	}


}
