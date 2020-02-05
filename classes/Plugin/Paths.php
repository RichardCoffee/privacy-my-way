<?php
/**
 *   Supplies basic plugin functions
 *
 * @package Privacy_My_Way
 * @subpackage Plugin_Core
 * @since 20170113
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Plugin/Paths.php
 */
defined( 'ABSPATH' ) || exit;
/**
 *  Function to provide easier access to Paths object.
 *
 * @since 20170404
 * @return object
 */
if ( ! function_exists( 'plugin_paths' ) ) {
	function plugin_paths() {
		static $instance = null;
		if ( empty( $instance ) ) {
			$instance = PMW_Plugin_Paths::instance();
		}
		return $instance;
	}
}

/**
 * @since 20170113
 */
class PMW_Plugin_Paths {

#	 * @since 20170113
	protected $file;
#	 * @since 20170113
	protected $dir;
#	 * @since 20170207
	protected $pages  = 'page-templates/';
#	 * @since 20170207
	protected $parts  = 'template-parts/';
#	 * @since 20170113
	protected $url;
#	 * @since 20180321
	protected $vendor = 'vendor/';
#	 * @since 20170113
	protected $version;

#	 * @since 20170116
	use PMW_Trait_Magic;
#	 * @since 20170310
	use PMW_Trait_ParseArgs;
#	 * @since 20170310
	use PMW_Trait_Singleton;

#	 * @since 20170113
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

#	 * @since 20170124
	public function add_plugin_template( $slug, $text ) {
		$file = $this->dir . $this->vendor . 'pagetemplater.php';
		if ( is_readable( $file ) ) {
			require_once( $file );
			$pager = PageTemplater::get_instance();
			$pager->add_project_template( $slug, $text, $this->dir );
		}
	}

#	 * @since 20170207
	public function get_plugin_file_path( $file, $force = false ) {
		$theme_check = get_theme_file_path( $file );
		if ( ( ! $force ) && $theme_check && is_readable( $theme_check ) ) {
			return $theme_check;
		} else if ( is_readable( $this->dir . $file ) ) {
			return $this->dir . $file;
		}
		return false;
	}

#	 * @since 20170207
	public function get_plugin_file_uri( $file, $force = false ) {
		$theme_check = get_theme_file_path( $file );
		if ( ( ! $force ) && $theme_check && is_readable( $theme_check ) ) {
			return get_theme_file_uri( $file );
		} else if ( is_readable( $this->dir . $file ) ) {
			return plugins_url( $file, $this->file );
		}
		return false;
	}


}
