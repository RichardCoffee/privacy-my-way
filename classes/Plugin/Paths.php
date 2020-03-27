<?php
/**
 *   Supplies plugin pathing functions and serves as an informational clearing house.
 *
 * @package Privacy_My_Way
 * @subpackage Plugin_Core
 * @since 20170113
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Plugin/Paths.php
 */
defined( 'ABSPATH' ) || exit;

class PMW_Plugin_Paths {

	/**
	 * @since 20170113
	 * @var string  Main plugin file name.
	 */
	protected $file;
	/**
	 * @since 20170113
	 * @var string  Plugin directory name.
	 */
	protected $dir;
	/**
	 * @since 20170207
	 * @var string  Location of plugin front end page templates.
	 */
	protected $pages  = 'page-templates/';
	/**
	 * @since 20170207
	 * @var string  Location of plugin front end template parts.
	 */
	protected $parts  = 'template-parts/';
	/**
	 * @since 20170113
	 * @var string  Base url of site.
	 */
	protected $url;
	/**
	 * @since 20180321
	 * @var string  Dir suffix for vendor files.
	 */
	protected $vendor = 'vendor/';
	/**
	 * @since 20170113
	 * @var string  Plugin version.
	 */
	protected $version;

	/**
	 * @since 20170116
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Magic.php
	 */
	use PMW_Trait_Magic;
	/**
	 * @since 20170310
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/ParseArgs.php
	 */
	use PMW_Trait_ParseArgs;
	/**
	 * @since 20170310
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Singleton.php
	 * @link http://rtcenterprises.net/php/php-and-the-singleton-trait/
	 */
	use PMW_Trait_Singleton;

	/**
	 *  Constructor method.
	 *
	 * @since 20170113
	 * @param array $args  Incoming data.
	 */
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

	/**
	 *  Add a page template.
	 *
	 * @since 20170124
	 * @param string $slug  Slug for the template file.
	 * @param string $text  Text to display in the template page dropdown.
	 */
	public function add_plugin_template( $slug, $text ) {
		$file = $this->dir . $this->vendor . 'pagetemplater.php';
		if ( is_readable( $file ) ) {
			require_once( $file );
			$pager = PageTemplater::get_instance();
			$pager->add_project_template( $slug, $text, $this->dir );
		}
	}

	/**
	 *  Retrieve the full path to a plugin file.  Will return the theme file path if it exists.
	 *
	 * @since 20170207
	 * @param string $file   Name of the file to retrieve the path to.
	 * @param bool   $force  Force use of the plugin file.
	 * @return string|bool   Path to the file, or boolean false if the file does not exist.
	 */
	public function get_plugin_file_path( $file, $force = false ) {
		$theme_check = get_theme_file_path( $file );
		if ( ( ! $force ) && $theme_check && is_readable( $theme_check ) ) {
			return $theme_check;
		} else if ( is_readable( $this->dir . $file ) ) {
			return $this->dir . $file;
		}
		return false;
	}

	/**
	 *  Retrieve the uri of a plugin file.  Will return the uri of a theme file, if it exists.
	 *
	 * @since 20170207
	 * @param string $file   Name of the file to retrieve the uri for.
	 * @param bool   $force  Force use of the plugin file.
	 * @return string|bool   Uri of the file, or boolean false if the file does not exist in either the plugin or the theme.
	 */
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
