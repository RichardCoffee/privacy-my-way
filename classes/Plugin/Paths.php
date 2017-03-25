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
		$file = $this->dir . 'assets/pagetemplater.php';
		if ( file_exists( $file ) ) {
			require_once( $file );
			$pager = PageTemplater::get_instance();
			$pager->add_project_template( $slug, $text, $this->dir );
		}
	}

	public function get_plugin_file_path( $file ) {
		$file_path   = false;
		$theme_check = get_theme_file_path( $file );
		if ( $theme_check && file_exists( $theme_check ) ) {
			$file_path = $theme_check;
		} else if ( file_exists( $this->dir . $file ) ) {
			$file_path = $this->dir . $file;
		}
		return $file_path;
	}

	public function get_plugin_file_uri( $file ) {
		$file_uri    = false;
		$theme_check = get_theme_file_path( $file );
		if ( $theme_check && file_exists( $theme_check ) ) {
			$file_uri = get_theme_file_uri( $file );
		} else {
			$file_uri = plugins_url( $file, $this->file );
		}
		return $file_uri;
	}


}
