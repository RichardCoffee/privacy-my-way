<?php

abstract class PMW_Options_Options {


	protected $base       = 'options'; # change this in child
	protected $capability = 'edit_theme_options';
	protected $priority   = 1000;      # change this in child
	protected $screen     = array();

	abstract protected function form_title();
	abstract protected function form_icon();
	abstract public    function describe_options();
	abstract protected function options_layout();

	public function __construct() {
		add_filter( 'fluidity_options_form_layout',        array( $this, 'form_layout' ),          $this->priority );
		add_filter( 'tcc_form_admin_options_localization', array( $this, 'options_localization' ), $this->priority );
	}

	public function form_layout( $form ) {
		if ( ! array_key_exists( $this->base, $form ) ) {
			$form[ $this->base ] = ( empty( $this->screen ) ) ? $this->default_form_layout() : $this->screen;
		}
		return $form;
	}

	public function default_form_layout() {
		if ( empty( $this->screen ) ) {
			$this->screen = array(
				'describe' => array( $this, 'describe_options' ),
				'title'    => $this->form_title(),
				'icon'     => $this->form_icon(),
				'option'   => 'tcc_options_' . $this->base,
				'layout'   => $this->options_layout(),
			);
		}
		return $this->screen;
	}

	/**
	 * add data to array passed to javascript
	 *
	 * @since 2.3.0
	 * @param array $data
	 * @return array
	 */
	public function options_localization( $data = array() ) {
		if ( ! array_key_exists( 'showhide', $data ) ) {
			$data['showhide'] = array();
		}
		$options = ( ! empty( $this->screen['layout'] ) ) ? $this->screen['layout'] : $this->options_layout();
		foreach( $options as $key => $item ) {
			if ( ! is_array( $item ) ) continue;
			if ( array_key_exists( 'showhide', $item ) ) {
				$data['showhide'][] = $item['showhide'];
			}
		}
		return $data;
	}

	public function get_default_options() {
		$form = $this->options_layout( true );
		$opts = array();
		foreach( $form as $key => $option ) {
			if ( array_key_exists( 'default', $option ) ) {
				$opts[ $key ] = $option['default'];
			}
		}
		return $opts;
	}

	public function get_item( $item ) {
		$layout = ( empty( $this->screen ) ) ? $this->options_layout() : $this->screen['layout'];
		return ( array_key_exists( $item, $layout ) ) ? $layout[ $item ] : array();
	}


}
