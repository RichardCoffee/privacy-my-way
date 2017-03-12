<?php

class PMW_Register_Privacy extends PMW_Register_Register {

	public  static $option   = 'privacy';
	private static $versions = array();

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
			self::$title = __( 'Privacy My Way', 'tcc-privacy' );
			$file = WP_PLUGIN_DIR . '/privacy-my-way.php';
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

	#	No theme dependencies
	protected static function theme_dependency() { }

}
