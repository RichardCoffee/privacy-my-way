<?php


class PMW_Options_Privacy extends PMW_Options_Options {

	private   $active   = array();
	protected $base     = 'privacy';
	private   $options  = array();
	protected $priority = 550;  #  internal theme option
	private   $plugins  = array();
	private   $themes   = array();

	private function initialize() {
		#	https://codex.wordpress.org/Function_Reference/get_plugins
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$this->plugins = ( $this->plugins ) ? $this->plugins : get_plugins();
		$this->active  = ( $this->active  ) ? $this->active  : get_option( 'active_plugins', array() );
		$this->themes  = ( $this->themes  ) ? $this->themes  : wp_get_themes();
	}

	protected function form_title() {
		return __( 'Privacy', 'tcc-privacy' );
	}

	public function describe_options() {
		esc_html_e( 'Control the information that WordPress collects from your site.  The default settings, marked by a (*), duplicate what WordPress currently collects.', 'tcc-privacy' );
	}

	protected function options_layout( $all = false ) {
		$this->initialize();
		$layout  = array( 'default' => true );
		$warning = _x( '*** Turning off reporting a %1$s means you will not be notified of upgrades for that %1$s! ***', 'noun - singular', 'tcc-privacy' );
		$extra_html = array( 'yes' => ' <span class="red"> ' . __( ' ( Recommended ) ', 'tcc-privacy' ) . '</span>' );
		$layout['blog'] = array(
			'default' => 'yes',
			'label'   => __( 'Blog URL', 'tcc-privacy' ),
#			'text'    => __( 'I would suggest that you not change this setting.', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'yes'  => __( "Let WordPress know your site's url. (*)", 'tcc-privacy' ),
				'no'   => __( 'Do not let them know where you are.', 'tcc-privacy' ),
			),
			'extra_html' => $extra_html,
			'divcss'     => 'privacy-blog-active',
		); //*/
		if ( ( is_multisite() && is_main_site() ) || $all ) {
			$layout['blog']['change'] = 'showhidePosi( this, ".privacy-blog-option", "yes" );';
			$layout['blogs'] = array(
				'default' => 'yes',
				'label'   => __( 'Multi-Site', 'tcc-privacy' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Yes - Let WordPress know if you are running a multi-site blog. (*)', 'tcc-privacy' ),
					'no'   => __( 'No -- Tell WordPress you are running just a single blog.', 'tcc-privacy' ),
				),
				'extra_html' => $extra_html,
				'change'  => 'showhidePosi( this, ".privacy-multi-option", "yes" );',
				'divcss'  => 'privacy-multi-active privacy-blog-option',
			); //*/
			$layout['install'] = array(
				'default' => 'yes',
				'label'   => __( 'Install URL', 'tcc-privacy' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Let WordPress know the url you installed WordPress to. (*)', 'tcc-privacy' ),
					'no'   => __( 'Do not give WordPress this information.', 'tcc-privacy' ),
				),
				'extra_html' => $extra_html,
				'divcss'  => 'privacy-blog-option privacy-multi-option',
			); //*/
		}
		$layout['users'] = array(
			'default' => 'all',
			'label'   => __( 'Users', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'all'  => __( 'Accurately report to WordPress how many users you have. (*)', 'tcc-privacy' ),
				'some' => __( 'Only let WordPress know that you have some users.', 'tcc-privacy' ),
				'one'  => __( 'Tell WordPress that you are the only user.', 'tcc-privacy' ),
				'many' => __( 'Just generate some random number to give WordPress.', 'tcc-privacy' ),
			),
		);
		$layout['plugins'] = array(
			'default'   => 'all',
			'label'     => __( 'Plugins', 'tcc-privacy' ),
			'render'    => 'radio',
			'source'    => array(
				'all'    => __( 'Let WordPress know what plugins you have installed. (*)', 'tcc-privacy' ),
				'active' => __( 'Only report active plugins.', 'tcc-privacy' ),
				'filter' => __( 'Filter the plugin list that gets sent to WordPress.', 'tcc-privacy' ),
				'none'   => __( 'Do not let them know about your plugins.', 'tcc-privacy' ),
			),
			'change'    => 'showhidePosi( this, ".privacy-plugin-filter", "filter" );',
			'divcss'    => 'privacy-plugin-active',
		); //*/
		$layout['install_default'] = array(
			'default' => 'yes',
			'label'   => __( 'Default', 'tcc-privacy' ),
			'text'    => __( 'Default setting for newly installed plugins/themes.', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'yes'  => __( 'Allow wordpress report on new installs. (*)', 'tcc-privacy' ),
				'no'   => __( 'Block reports on new installs.', 'tcc-privacy' ),
			),
			'extra_html' => $extra_html,
			'divcss'  => 'privacy-plugin-filter',
		);
		$layout['plugin_list'] = array(
			'default' => $this->get_plugin_defaults( ),
			'preset'  => 'yes',
			'label'   => __( 'Plugin List', 'tcc-privacy' ),
			'text'    => sprintf( $warning, __( 'plugin', 'tcc-privacy' ) ),
			'textcss' => 'red', // FIXME: bad css
			'render'  => 'radio_multiple',
			'source'  => $this->get_plugin_list(),
			'divcss'  => 'privacy-plugin-filter',
		); //*/
		$layout['themes'] = array(
			'default' => 'all',
			'label'   => __( 'Themes', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'all'    => __( 'Let WordPress know what themes you have installed. (*)', 'tcc-privacy' ),
				'active' => __( 'Only let them know about your active theme.', 'tcc-privacy' ),
				'filter' => __( 'Filter the theme list that gets sent to WordPress.', 'tcc-privacy' ),
				'none'   => __( 'Do not let them know about your themes.', 'tcc-privacy' ),
			),
			'change'  => 'showhidePosi(this,".privacy-theme-filter","filter");',
			'divcss'  => 'privacy-theme-active',
		); //*/
		$layout['theme_list'] = array(
			'default' => $this->get_theme_defaults(),
			'preset'  => 'yes',
			'label'   => __( 'Theme List', 'tcc-privacy' ),
			'text'    => sprintf( $warning, __( 'theme', 'tcc-privacy' ) ),
			'textcss' => 'red', // FIXME: bad css
			'postext' => __( 'The WordPress twenty* themes that are installed will always be reported.', 'tcc-privacy' ),
			'help'    => __( 'This plugin does not filter default WordPress themes.', 'tcc-privacy' ),
			'render'  => 'radio_multiple',
/*			'titles'  => array(
				__( 'On', 'tcc-privacy' ),
				__( 'Off', 'tcc-privacy' ),
				__( 'Description', 'tcc-privacy' ),
			), //*/
			'source'  => $this->get_theme_list(),
			'divcss'  => 'privacy-theme-filter',
		); //*/
		$layout['autoupdate'] = array(
			'default' => 'yes',
			'label'   => __( 'WP Updates', 'tcc-privacy' ),
			'text'    => __( 'Allow/prevent WordPress automatic updates.', 'tcc-privacy' ),
			'postext' => __( 'This will only stop automatic updates, it will not make them happen.', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'yes' => __( 'Allow WordPress to perform automatic updates.', 'tcc-privacy' ),
				'no'  => __( 'Prevent WordPress from doing automatic updates.', 'tcc-privacy' ),
			),
			'extra_html' => $extra_html,
		);
		$layout['plugindata'] = array(
			'label'   => __( 'Plugin Data', 'tcc-privacy' ),
			'text'    => __( 'Plugin Settings.', 'tcc-privacy' ),
			'render'  => 'title',
		);
		if ( WP_DEBUG || $all ) {
			$layout['logging'] = array(
				'default' => 'off',
				'label'   => __( 'Logging', 'tcc-privacy' ),
				'text'    => __( 'Logging Status.', 'tcc-privacy' ),
				'render'  => 'radio',
				'source'  => array(
					'off' => __( 'Do not log anything.', 'tcc-privacy' ),
					'on'  => __( 'Log everything.', 'tcc-privacy' ),
				),
			);
		}
		$layout['deledata'] = array(
			'default' => 'uninstall',
			'label'   => __( 'Data Deletion', 'tcc-privacy' ),
			'text'    => __( 'Control when plugin data is removed.', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'deactive'  => __( 'Delete plugin data upon plugin deactivation.', 'tcc-privacy' ),
				'uninstall' => __( 'Delete plugin data upon plugin deletion.', 'tcc-privacy' ),
				'nodelete'  => __( 'Do not delete plugin data.', 'tcc-privacy' ),
			),
		);
		$layout = apply_filters( "tcc_options_layout_{$this->base}", $layout );
		return $layout;
	}


	/**  Plugin functions  **/

	public function get_plugin_defaults( ) {
		#	Start with a clean slate
		$options = $this->clean_plugin_defaults();
		#	Load missing items with the default value, with new actives getting an automatic 'yes'
		$preset = $this->get_option( 'install_default', 'yes' );
		foreach( $this->plugins as $path => $plugin ) {
			if ( ! isset( $options[ $path ] ) || empty( $options[ $path ] ) ) {
				$options[ $path ] = ( in_array( $path, $this->active ) ) ? 'yes' : $preset;
				if ( $path === 'privacy-my-way/privacy-my-way.php' ) {
					$options[ $path ] = 'no';  #  Set our own initial value
				}
			}
		}
		return $options;
	}

	#	Removes deleted plugins by generating a new list
	private function clean_plugin_defaults() {
		#	The beginning
		$options = array();
		$current = $this->get_option( 'plugin_list', array() );
		foreach( $current as $key => $status ) {
			if ( isset( $this->plugins[ $key ] ) ) {
				$options[ $key ] = $status;
			}
		}
		return $options;
	}

	private function get_plugin_list() {
		$plugin_list = array();
		foreach ( $this->plugins as $path => $plugin ) {
			$title  = '<a href="' . esc_attr( $plugin['PluginURI'] ) . '" target="' . esc_attr( $path ) . '">';
			$title .= esc_html( $plugin['Name'] ) . '</a>';
			if ( in_array( $path, $this->active ) ) {
				$status = sprintf( '<span class="pmw-plugin-active">(%s)</span>', esc_html__( 'active', 'tcc-privacy' ) );
			} else {
				$status = sprintf( '<span class="pmw-plugin-inactive">(%s)</span>', esc_html__( 'inactive', 'tcc-privacy' ) );
			}
			$author  = '<a href="' . esc_attr( $plugin['AuthorURI'] ) . '" target="' . sanitize_title( $plugin['Author'] ) . '">';
			$author .= esc_html( $plugin['Author'] ) . '</a>';
			$plugin_list[ $path ] = sprintf( esc_html_x( '%1$s %2$s by %3$s', '1: plugin title, 2: plugin active/inactive status, 3: plugin author name', 'tcc-privacy' ), $title, $status, $author );
		}
		return $plugin_list;
	}


	/**  Theme functions  **/

	private function get_theme_defaults() {
		$options = $this->clean_theme_defaults();
		$preset  = $this->get_option( 'install_default', 'yes' );
		foreach( $this->themes as $slug => $theme ) {
			if ( ! isset( $options[ $slug ] ) || empty( $options[ $slug ] ) ) {
				$options[ $slug ] = ( stripos( $slug, 'twenty' ) === false ) ? $preset : 'yes';
			}
		}
		return $options;
	}

	#	removes deleted themes by generating a new list
	private function clean_theme_defaults() {
		$options = array();
		$current = $this->get_option( 'theme_list', array() );
		foreach( $current as $key => $status ) {
			if ( isset( $this->plugins[ $key ] ) ) {
				$options[ $key ] = $status;
			}
		}
		return $options;
	}

	private function get_theme_list() {
		$theme_list = array();
		foreach( $this->themes as $slug => $theme ) {
			if ( strpos( $slug, 'twenty' ) === 0 ) {
				continue;  #  Do not allow filtering of wordpress themes
			}
			$title  = '<a href="' . esc_attr( $theme->get( 'ThemeURI' ) ) . '" target="' . esc_attr( $slug ) . '">';
			$title .= esc_html( $theme->get( 'Name' ) ) . '</a>';
			$author = '<a href="' . esc_attr( $theme->get( 'AuthorURI' ) ) . '" target="' . sanitize_title( $theme->get( 'Author' ) ) . '">';
			$author.= esc_html( $theme->get( 'Author' ) ) . '</a>';
			$theme_list[ $slug ] = sprintf( esc_html_x( '%1$s by %2$s', '1: Theme title, 2: Author name', 'tcc-privacy' ), $title, $author );
		}
		return $theme_list;
	}

	private function get_option( $option, $value = '' ) {
		if ( empty( $this->options ) ) {
			$this->options = get_option( 'tcc_options_privacy', array() );
		}
		if ( isset( $this->options[ $option ] ) ) {
			$value = $this->options[ $option ];
		}
		return $value;
	}

}
