<?php

class TCC_Plugin_Paths {

	protected $file;
	protected $dir;
	protected $pages = '/page-templates/';
	protected $parts = '/template-parts/';
	protected $url;
	protected $version;

	use TCC_Trait_Magic;

	public function __construct( $args ) {
		foreach ( $args as $key => $arg ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $arg; }
		}
	}


	/**  Template functions  **/

	public function add_plugin_template( $slug, $text ) {
		require_once( $this->dir . 'classes/pagetemplater.php' );
		$pager = PageTemplater::get_instance();
		$pager->add_project_template( $slug, $text, $this->dir );
	}

	public function get_plugin_file_path( $slug ) {
		$theme_check = get_theme_file_path( $slug );
		if ( file_exists( $theme_check ) ) {
			$file_path = $theme_check;
		} else {
			$file_path = plugins_dir( $file );
		}
		return $file_path;
	}

	public function get_plugin_file_uri( $file ) {
		$theme_check = get_theme_file_path( $slug );
		if ( file_exists( $theme_check ) ) {
			$file_path = get_theme_file_uri( $slug );
		} else {
			$file_path = plugins_url( $file );
		}
		return $file_path;
	}


}
