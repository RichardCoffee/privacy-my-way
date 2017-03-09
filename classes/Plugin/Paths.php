<?php

class PMW_Plugin_Paths {

	protected $file;
	protected $dir;
	protected $pages = '/page-templates/';
	protected $parts = '/template-parts/';
	protected $url;
	protected $version;

	use PMW_Trait_Magic;
	use PMW_Trait_ParseArgs;
	use PMW_Trait_Singleton;

	protected function __construct( $args ) {
		$this->parse_args( $args );
		$this->dir = trailingslashit( $this->dir );
	}

	/**  Template functions  **/

	public function add_plugin_template( $slug, $text ) {
		require_once( $this->dir . 'classes/pagetemplater.php' );
		$pager = PageTemplater::get_instance();
		$pager->add_project_template( $slug, $text, $this->dir );
	}

	public function get_plugin_file_path( $slug ) {
		$file_path   = false;
		$theme_check = get_theme_file_path( $slug );
		if ( file_exists( $theme_check ) ) {
			$file_path = $theme_check;
		} else if ( file_exists( WP_PLUGIN_DIR . '/'. $slug ) ) {
			$file_path = WP_PLUGIN_DIR . '/'. $slug;
		}
		return $file_path;
	}

	public function get_plugin_file_uri( $file ) {
		$theme_check = get_theme_file_path( $slug );
		if ( file_exists( $theme_check ) ) {
			$file_path = get_theme_file_uri( $slug );
		} else {
			$file_path = plugins_url( $file, $this->file );
		}
		return $file_path;
	}


}
