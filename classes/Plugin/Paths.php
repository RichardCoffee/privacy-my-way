<?php

if ( ! function_exists( 'plugin_paths' ) ) {
	function plugin_paths() {
		static $instance = null;
		if ( empty( $instance ) ) {
			$instance = PMW_Plugin_Paths::instance();
		}
		return $instance;
	}
}

class PMW_Plugin_Paths {

	protected $file;
	protected $dir;
	protected $pages  = 'page-templates/';
	protected $parts  = 'template-parts/';
	protected $url;
	protected $vendor = 'vendor/';
	protected $version;

	use PMW_Trait_Magic;
	use PMW_Trait_ParseArgs;
	use PMW_Trait_Singleton;

	protected function __construct( $args = array() ) {
		if ( ! empty( $args['file'] ) ) {
			$this->parse_args( $args );
			$this->dir    = trailingslashit( $this->dir );
			$this->pages  = trailingslashit( $this->pages );
			$this->parts  = trailingslashit( $this->parts );
			$this->vendor = trailingslashit( $this->vendor );
		} else {
			static::$abort__construct = true;
		}
	}

	/**  Template functions  **/

	public function add_plugin_template( $slug, $text ) {
		$file = $this->dir . $this->vendor . 'pagetemplater.php';
		if ( is_readable( $file ) ) {
			require_once( $file );
			$pager = PageTemplater::get_instance();
			$pager->add_project_template( $slug, $text, $this->dir );
		}
	}

	public function get_plugin_file_path( $file, $force = false ) {
		$file_path   = false;
		$theme_check = get_theme_file_path( $file );
		if ( ( ! $force ) && $theme_check && is_readable( $theme_check ) ) {
			$file_path = $theme_check;
		} else if ( is_readable( $this->dir . $file ) ) {
			$file_path = $this->dir . $file;
		}
		return $file_path;
	}

	public function get_plugin_file_uri( $file, $force = false ) {
		$file_uri    = false;
		$theme_check = get_theme_file_path( $file );
		if ( ( ! $force ) && $theme_check && is_readable( $theme_check ) ) {
			$file_uri = get_theme_file_uri( $file );
		} else if ( is_readable( $this->dir . $file ) ) {
			$file_uri = plugins_url( $file, $this->file );
		}
		return $file_uri;
	}


}
