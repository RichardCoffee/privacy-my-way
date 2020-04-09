<?php
/**
 * @since 20170301
 */
defined('ABSPATH') || exit;


final class PMW_Options_Privacy extends PMW_Options_Options {

	/**
	 * @since 20170301
	 * @var array  Active plugins.
	 */
	private $active = array();
	/**
	 * @since 20170301
	 * @var string
	 */
	protected $base = 'privacy-my-way';
	/**
	 * @since 20170404
	 * @var array  Plugin option settings.
	 */
	private $options = array();
	/**
	 * @since 20170301
	 * @var int  Used by Fluidity theme options for tab positioning.
	 */
	protected $priority = 550;
	/**
	 * @since 20170301
	 * @var array  List of installed plugins.
	 */
	private $plugins = array();
	/**
	 * @since 20170301
	 * @var array  List of installed themes.
	 */
	private $themes = array();

	/**
	 *  Get values for class properties.
	 *
	 * @since 20170404
	 * @link https://codex.wordpress.org/Function_Reference/get_plugins
	 */
	private function initialize() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->plugins = ( $this->plugins ) ? $this->plugins : get_plugins();
		$this->active  = ( $this->active  ) ? $this->active  : get_option( 'active_plugins', array() );
		$this->themes  = ( $this->themes  ) ? $this->themes  : wp_get_themes();
	}

	/**
	 *  Returns the form title.
	 *
	 * @since 20170415
	 * @return string
	 */
	protected function form_title() {
		return __( 'Privacy', 'privacy-my-way' );
	}

	/**
	 *  Returns the CSS class for the tab icon.
	 *
	 * @since 20180830
	 * @return string
	 */
	protected function form_icon() {
		return 'dashicons-admin-network';
	}

	/**
	 *  Displays a description on the settings screen.
	 *
	 * @since 20170301
	 */
	public function describe_options() {
		esc_html_e( 'Control the information that WordPress collects from your site.  The default settings, marked by a (*), duplicate what WordPress currently collects.', 'privacy-my-way' );
	}

	/**
	 *  Returns the layout for the settings screen.
	 *
	 * @since 20170301
	 * @param bool  Force return of all options.
	 * @return array
	 */
	protected function options_layout( $all = false ) {
		$this->initialize();
		$layout  = array( 'default' => true );
		$warning = _x( '*** Turning off reporting a %1$s means you will not be notified of upgrades for that %1$s! ***', 'noun - singular', 'privacy-my-way' );
		$extra_html = array( 'yes' => ' <span class="red"> ' . esc_html_x( ' ( Recommended ) ', 'Added to a string to indicate the recommended option', 'privacy-my-way' ) . '</span>' );
		$layout['blog'] = array(
			'default' => 'yes',
			'label'   => __( 'Blog URL', 'privacy-my-way' ),
			'text'    => __( 'I would suggest that you not change this setting.', 'privacy-my-way' ),
			'render'  => 'radio',
			'source'  => array(
				'yes'  => __( "Let WordPress know your site's url. (*)", 'privacy-my-way' ),
				'no'   => __( 'Do not let them know where you are.', 'privacy-my-way' ),
			),
			'extra_html' => $extra_html,
			'divcss'     => 'privacy-blog-active',
		); //*/
		$layout['browser'] = array(
			'default' => 'yes',
			'label'   => __( 'Browser', 'privacy-my-way' ),
			'text'    => __( 'Turning this off prevents wordpress.org from informing you if your browser is considered unsecure or outdated.', 'privacy-my-way'),
			'render'  => 'radio',
			'source'  => array(
				'yes'  => __( 'Let WordPress know what browser is being used to view admin screens. (*)', 'privacy-my-way' ),
				'no'   => __( 'Do not let them know what browser you use.', 'privacy-my-way' ),
			),
		);
		$layout['location'] = array(
			'default' => 'yes',
			'label'   => __( 'Location', 'privacy-my-way' ),
			'text'    => __( 'Disabling this option will cause the Community Events Dashboard widget to not automatically display nearby events.', 'privacy-my-way' ),
			'render'  => 'radio',
			'source'  => array(
				'yes'  => __( 'Let WordPress know your IP address, locale, and timezone. (*)', 'privacy-my-way' ),
				'no'   => __( 'Do not let them know where you are at.', 'privacy-my-way' ),
			),
		);
		if ( ( is_multisite() && is_main_site() ) || $all ) {
			$layout['blog']['showhide'] = array(
				'origin' => 'privacy-blog-active',
				'target' => 'privacy-blog-option',
				'show'   => 'yes',
			);
			$layout['blogs'] = array(
				'default' => 'yes',
				'label'   => __( 'Multi-Site', 'privacy-my-way' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Yes - Let WordPress know if you are running a multi-site blog. (*)', 'privacy-my-way' ),
					'no'   => __( 'No -- Tell WordPress you are running just a single blog.', 'privacy-my-way' ),
				),
				'extra_html' => $extra_html,
				'showhide' => array(
					'origin' => 'privacy-multi-active',
					'target' => 'privacy-multi-option',
					'show'   => 'yes',
				),
				'divcss'  => 'privacy-multi-active privacy-blog-option',
			); //*/
			$layout['install'] = array(
				'default' => 'yes',
				'label'   => __( 'Install URL', 'privacy-my-way' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Let WordPress know the url you installed WordPress to. (*)', 'privacy-my-way' ),
					'no'   => __( 'Do not give WordPress this information.', 'privacy-my-way' ),
				),
				'extra_html' => $extra_html,
				'divcss'  => 'privacy-blog-option privacy-multi-option',
			); //*/
		}
		$layout['users'] = array(
			'default' => 'all',
			'label'   => __( 'Users', 'privacy-my-way' ),
			'text'    => __( 'Be aware that every server between you and wordpress.org gets to see this information.', 'privacy-my-way' ),
			'render'  => 'radio',
			'source'  => array(
				'all'  => __( 'Accurately report to WordPress how many users you have. (*)', 'privacy-my-way' ),
				'some' => __( 'Only let WordPress know that you have some users.', 'privacy-my-way' ),
				'one'  => __( 'Tell WordPress that you are the only user.', 'privacy-my-way' ),
				'many' => __( 'Just generate some random number to give WordPress.', 'privacy-my-way' ),
			),
		);
		$layout['plugins'] = array(
			'default'   => 'all',
			'label'     => __( 'Plugins', 'privacy-my-way' ),
			'render'    => 'radio',
			'source'    => array(
				'all'    => __( 'Let WordPress know what plugins you have installed. (*)', 'privacy-my-way' ),
				'active' => __( 'Only report active plugins.', 'privacy-my-way' ),
				'filter' => __( 'Filter the plugin list that gets sent to WordPress.', 'privacy-my-way' ),
				'none'   => __( 'Do not let them know about your plugins.', 'privacy-my-way' ),
			),
			'showhide'  => array(
				'origin' => 'privacy-plugin-active',
				'target' => 'privacy-plugin-filter',
				'show'   => 'filter'
			),
			'divcss'    => 'privacy-plugin-active',
		); //*/
		$layout['install_default'] = array(
			'default' => 'yes',
			'label'   => __( 'Installs', 'privacy-my-way' ),
			'text'    => __( 'Default setting for newly installed plugins/themes.', 'privacy-my-way' ),
			'render'  => 'radio',
			'source'  => array(
				'yes'  => __( 'Allow wordpress report on new installs. (*)', 'privacy-my-way' ),
				'no'   => __( 'Block reports on new installs.', 'privacy-my-way' ),
			),
			'extra_html' => $extra_html,
			'divcss'  => 'privacy-plugin-filter',
		);
		$layout['plugin_list'] = array(
			'default' => $this->get_plugin_defaults( ),
			'preset'  => 'yes',
			'label'   => __( 'Plugin List', 'privacy-my-way' ),
			'text'    => sprintf( $warning, __( 'plugin', 'privacy-my-way' ) ),
			'textcss' => 'red',
			'render'  => 'radio_multiple',
			'source'  => $this->get_plugin_list(),
			'divcss'  => 'privacy-plugin-filter',
		);
		$layout['themes'] = array(
			'default' => 'all',
			'label'   => __( 'Themes', 'privacy-my-way' ),
			'render'  => 'radio',
			'source'  => array(
				'all'    => __( 'Let WordPress know what themes you have installed. (*)', 'privacy-my-way' ),
				'active' => __( 'Only let them know about your active theme.', 'privacy-my-way' ),
				'filter' => __( 'Filter the theme list that gets sent to WordPress.', 'privacy-my-way' ),
				'none'   => __( 'Do not let them know about your themes.', 'privacy-my-way' ),
			),
			'showhide' => array(
				'origin' => 'privacy-theme-active',
				'target' => 'privacy-theme-filter',
				'show'   => 'filter',
			),
			'divcss'  => 'privacy-theme-active',
		); //*/
		$layout['theme_list'] = array(
			'default' => $this->get_theme_defaults(),
			'preset'  => 'yes',
			'label'   => __( 'Theme List', 'privacy-my-way' ),
			'text'    => sprintf( $warning, __( 'theme', 'privacy-my-way' ) ),
			'textcss' => 'red',
			'postext' => __( 'The WordPress twenty* themes that are installed will always be reported.', 'privacy-my-way' ),
			'help'    => __( 'This plugin does not filter default WordPress themes.', 'privacy-my-way' ),
			'render'  => 'radio_multiple',
			'source'  => $this->get_theme_list(),
			'divcss'  => 'privacy-theme-filter',
		); //*/
		if ( WP_DEBUG || $all ) {
			$layout['autoupdate'] = array(
				'default' => 'yes',
				'label'   => __( 'WP Updates', 'privacy-my-way' ),
				'text'    => __( 'Allow/prevent WordPress automatic updates.  You should not need to set this at all.', 'privacy-my-way' ),
				'postext' => __( 'This will only stop automatic updates, it will not make them happen.', 'privacy-my-way' ),
				'help'    => __( 'WordPress generally does the right thing here.  I recommend the default Allow.', 'privacy-my-way' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Allow WordPress to perform automatic updates.', 'privacy-my-way' ),
					'core' => __( 'Core automatic updates only.', 'privacy-my-way' ),
					'no'   => __( 'Prevent WordPress from doing any automatic updates.', 'privacy-my-way' ),
				),
				'extra_html' => $extra_html,
			);
		}
		$layout['plugindata'] = array(
			'label'   => __( 'Plugin Data', 'privacy-my-way' ),
			'text'    => __( 'Settings for the Privacy My Way plugin.', 'privacy-my-way' ),
			'render'  => 'title',
		);
#		if ( WP_DEBUG || $all ) {
			$layout['logging'] = array(
				'default' => 'off',
				'label'   => __( 'Logging', 'privacy-my-way' ),
				'text'    => __( 'Logging Status.', 'privacy-my-way' ),
				'render'  => 'radio',
				'source'  => array(
					'off' => __( 'Do not log anything.', 'privacy-my-way' ),
					'on'  => __( 'Log everything.', 'privacy-my-way' ),
				),
			);
#		} //*/
		$layout['deledata'] = array(
			'default' => ( WP_DEBUG ) ? 'nodelete' : 'uninstall',
			'label'   => __( 'Data Deletion', 'privacy-my-way' ),
			'text'    => __( 'Control when plugin data is deleted.', 'privacy-my-way' ),
			'render'  => 'radio',
			'source'  => array(
				'deactive'  => __( 'Deactivation of the plugin.', 'privacy-my-way' ),
				'uninstall' => __( 'Deletion of the plugin.', 'privacy-my-way' ),
				'nodelete'  => __( 'Always retain the plugin data.', 'privacy-my-way' ),
			),
		);
		return $layout;
	}


	/**  Plugin functions  **/

	/**
	 *  Gets, and possibly sets, the default values for reporting plugins.
	 *
	 * @since 20170301
	 * @return array
	 */
	public function get_plugin_defaults( ) {
		$options = $this->get_option( 'plugin_list', array() );
		$preset  = $this->get_option( 'install_default', 'yes' );
		foreach( $this->plugins as $key => $plugin ) {
			if ( ! array_key_exists( $key, $options ) || empty( $options[ $key ] ) ) {
				#	Load missing items with the default value, with new actives getting an automatic 'yes'
				$options[ $key ] = ( in_array( $key, $this->active ) ) ? 'yes' : $preset;
				if ( strpos( $key, 'privacy-my-way' ) === 0 ) {
					$options[ $key ] = 'no';  #  Set our own initial value
				}
			}
		}
		return $options;
	}

	/**
	 *  Creates the plugin list for the settings screen.
	 *
	 * @since 20170301
	 * @return array
	 */
	private function get_plugin_list() {
		$plugin_list  = array();
		$title_label  = __( 'Plugin website', 'privacy-my-way' );
		$author_label = __( 'Plugin author',  'privacy-my-way' );
		$active   = sprintf( '<span class="pmw-plugin-active">(%s)</span>',   esc_html__( 'active',   'privacy-my-way' ) );
		$inactive = sprintf( '<span class="pmw-plugin-inactive">(%s)</span>', esc_html__( 'inactive', 'privacy-my-way' ) );
		$format   = esc_html_x( '%1$s %2$s by %3$s', '1: plugin title, 2: plugin active/inactive status, 3: plugin author name', 'privacy-my-way' );
		$library  = new PMW_Plugin_Library;
		foreach ( $this->plugins as $key => $plugin ) {
			if ( empty( $plugin['PluginURI'] ) ) {
				$title = wp_strip_all_tags( $plugin['Name'] );
			} else {
				$title_attrs = array(
					'href'   => $plugin['PluginURI'],
					'target' => $key,
					'title'  => $title_label,
					'aria-label' => $title_label,
				);
				$title = $library->get_element( 'a', $title_attrs, $plugin['Name'] );
			}
			$status = ( in_array( $key, $this->active ) ) ? $active : $inactive;
			if ( empty( $plugin['AuthorURI'] ) ) {
				$author = wp_strip_all_tags( $plugin['Author'] );
			} else {
				$author_attrs = array(
					'href'   => $plugin['AuthorURI'],
					'target' => sanitize_title( $plugin['Author'] ),
					'title'  => $author_label,
					'aria-label' => $author_label,
				);
				$author = $library->get_element( 'a', $author_attrs, $plugin['Author'] );
			}
			$plugin_list[ $key ] = sprintf( $format, $title, $status, $author );
		}
		return $plugin_list;
	}


	/**  Theme functions  **/

	/**
	 *  Gets, and possibly sets, default values for reporting themes.
	 *
	 * @since 20170301
	 * @return array
	 */
	private function get_theme_defaults() {
		$options = $this->get_option( 'theme_list', array() );
		$preset  = $this->get_option( 'install_default', 'yes' );
		foreach( $this->themes as $key => $theme ) {
			if ( ! array_key_exists( $key, $options ) || empty( $options[ $key ] ) ) {
				$options[ $key ] = ( stripos( $key, 'twenty' ) === false ) ? $preset : 'yes';
			}
		}
		return $options;
	}

	/**
	 *  Get the list of themes for the settings screen.
	 *
	 * @since 20170301
	 * @return array
	 */
	private function get_theme_list() {
		$theme_list   = array();
		$theme_label  = __( 'Theme website', 'privacy-my-way' );
		$author_label = __( 'Theme author', 'privacy-my-way' );
		$format  = esc_html_x( '%1$s by %2$s', '1: Theme title, 2: Author name', 'privacy-my-way' );
		$library = new PMW_Plugin_Library;
		foreach( $this->themes as $key => $theme ) {
			//  Do not filter wordpress themes.
			if ( strpos( $key, 'twenty' ) === 0 ) continue;
			$title_attrs = array(
				'href'   => $theme->get( 'ThemeURI' ),
				'target' => $key,
				'title'  => $theme_label,
				'aria-label' => $theme_label,
			);
			$title  = $library->get_element( 'a', $title_attrs, $theme->get( 'Name' ) );
			$author_attrs = array(
				'href'   => $theme->get( 'AuthorURI' ),
				'target' => sanitize_title( $theme->get( 'Author' ) ),
				'title'  => $author_label,
				'aria-label' => $author_label,
			);
			$author = $library->get_element( 'a', $author_attrs, $theme->get( 'Author' ) );
			$theme_list[ $key ] = sprintf( $format, $title, $author );
		}
		return $theme_list;
	}

	/**
	 *  Returns the requested option.
	 *
	 * @since 20170404
	 * @param string $option Option slug.
	 * @param mixed  $value  Default value.
	 * @return mixed         The option value.
	 */
	private function get_option( $option, $value = '' ) {
		if ( empty( $this->options ) ) {
			$this->options = get_option( 'tcc_options_privacy-my-way', array() );
		}
		if ( array_key_exists( $option, $this->options ) ) {
			$value = $this->options[ $option ];
		}
		return $value;
	}

	/**
	 *  Returns customizer data for settings options.
	 *
	 * @since 20180404
	 * @return array
	 */
	protected function customizer_data() {
		$data = array(
			array(
			),
		);
		return apply_filters( "fluid_{$this->base}_customizer_data", $data );
	}

}
