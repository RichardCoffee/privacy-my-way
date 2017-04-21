<?php

class PMW_Register_Privacy extends PMW_Register_Register {

	public    static $option      = 'privacy';
	private   static $versions    =  array();
	protected static $plugin_file = 'privacy-my-way/privacy-my-way.php';

	protected static function activate_tasks() {
		self::initialize_options();
#		self::remove_update_transients();
	}

	private static function initialize_options() {
		$options = get_option( 'tcc_options_privacy', array() );
		if ( empty( $options ) ) {
			$privacy = new PMW_Options_Privacy;
			$options = $privacy->get_default_options();
			$options['plugin_list']['privacy-my-way/privacy-my-way.php'] = 'no';
			update_option( 'tcc_options_privacy', $options );
		}
	}

	protected static function php_version_required() {
		$php = self::get_required_version( 'PHP' );
		return ( $php ) ? $php : parent::php_version_required();
	}

	protected static function wp_version_required() {
		$wp = self::get_required_version( 'WP' );
		return ( $wp ) ? $wp : parent::wp_version_required();
	}

	private static function get_required_version( $request ) {
		if ( empty( self:: $versions ) ) {
			static::$title = __( 'Privacy My Way', 'tcc-privacy' );
			$file = WP_PLUGIN_DIR . '/' . self::$plugin_file;
			$need = array(
				'PHP' => 'Required PHP',
				'WP'  => 'Requires at least',
			);
			self::$versions = get_file_data( $file, $need );
		}
		if ( isset( self::$versions[ $request ] ) ) {
			return self::$versions[ $request ];
		}
		return false;
	}

	private static function remove_update_transients() {
		$transients = array(
			'update_core',
			'update_plugins',
			'update_themes',
		);
		foreach( $transients as $transient ) {
			if ( $check = get_site_transient( $transient ) ) {
				delete_site_transient( $transient );
			}
		}
	}

	#	No theme dependencies
	protected static function theme_dependency() { }

}
