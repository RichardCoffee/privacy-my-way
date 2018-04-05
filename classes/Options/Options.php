<?php

abstract class PMW_Options_Options {


	protected $base       = 'options'; # change this in child
	protected $capability = 'edit_theme_options';
	protected $priority   = 1000;      # change this in child
	protected $screen     = array();

	abstract protected function form_title();
	abstract public    function describe_options();
	abstract protected function options_layout();
	abstract protected function customizer_data();


	public function __construct() {
		add_filter( 'fluidity_options_form_layout',        array( $this, 'form_layout' ),          $this->priority );
		add_filter( 'tcc_form_admin_options_localization', array( $this, 'options_localization' ), $this->priority );
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

	public function options_localization( $data = array() ) {
		if ( ! isset( $data['showhide'] ) ) {
			$data['showhide'] = array();
		}
		foreach( $this->screen['layout'] as $key => $item ) {
			if ( isset( $item['showhide'] ) ) {
				$data['showhide'][] = $item['showhide'];
			}
		}
		return $data;
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


/***   WP Customizer   ***/

	public function customizer( WP_Customize_Manager $customize ) {
		$data   = $this->customizer_data();
		$layout = $this->options_layout();
		foreach( $data as $section ) {
			$priority     = 0;
			$section_id   = 'fluid_' . $section['id'];
			$section_args = $this->customizer_section( $layout[ $section['section'] ] );
			$customize->add_section( $section_id, $section_args );
			foreach( $section['controls'] as $control ) {
				$item         = $layout[ $control ];
				$priority    += 10;
				$setting_id   = $section_id . '_' . $control;
				$setting_args = $this->customizer_setting( $item );
				$customize->add_setting( $setting_id, $setting_args );
				new PMW_Form_Customizer( compact( 'customize', 'section_id', 'setting_id', 'item', 'priority' ) );
			}
		}
	}

	protected function customizer_section( $item ) {
		$args = array(
			'priority'           => ( isset( $item['priority'] ) )   ? $item['priority']           : $this->priority,
			'panel'              => ( isset( $item['panel'] ) )      ? $item['panel']              : '',
			'capability'         => ( isset( $item['capability'] ) ) ? $item['capability']         : $this->capability,
#			'theme_supports'     => ( isset( $item['theme_supports'] ) ) ? $item['theme_supports'] : '', // plugins only
			'title'              => $item['label'],
			'description'        => $item['text'],
#			'type'               =>
#			'active_callback'    => // does this determine if the section is displayed/hidden/disabled/what?
			'description_hidden' => true,
		);
		return $args;
	}

	protected function customizer_setting( $item ) {
		$args = array(
			'type'                 => ( isset( $item['type'] ) )           ? $item['type']           : 'option',
			'capability'           => ( isset( $item['capability'] ) )     ? $item['capability']     : $this->capability,
#			'theme_supports'       => ( isset( $item['theme_supports'] ) ) ? $item['theme_supports'] : '', // plugins only
			'default'              => ( isset( $item['default'] ) )        ? $item['default']        : '',
			'transport'            => ( isset( $item['transport'] ) )      ? $item['transport']      : 'refresh', // 'postMessage'
			'validate_callback'    => ( isset( $item['validate'] ) )       ? $item['validate']       : '', // when is this called?
			'sanitize_callback'    => ( isset( $item['sanitize'] ) )       ? $item['sanitize']       : array( fluid_sanitize(), $item['render'] ),
			'sanitize_js_callback' => ( isset( $item['js_callback'] ) )    ? $item['js_callback']    : '',
			'dirty'                => ( isset( $item['dirty'] ) )          ? $item['dirty']          : array(), // wtf?
		);
		return $args;
	}


}
