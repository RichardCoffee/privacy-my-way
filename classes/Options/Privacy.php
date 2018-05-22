<?php


final class PMW_Options_Privacy extends PMW_Options_Options {

	private   $active   = array();
	protected $base     = 'privacy-my-way';
	private   $library;
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
		$this->library = new PMW_Plugin_Library;
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
			$layout['blog']['showhide'] = array(
				'origin' => 'privacy-blog-active',
				'target' => 'privacy-blog-option',
				'show'   => 'yes',
			);
			$layout['blogs'] = array(
				'default' => 'yes',
				'label'   => __( 'Multi-Site', 'tcc-privacy' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Yes - Let WordPress know if you are running a multi-site blog. (*)', 'tcc-privacy' ),
					'no'   => __( 'No -- Tell WordPress you are running just a single blog.', 'tcc-privacy' ),
				),
				'extra_html' => $extra_html,
				'showhide' => array(
					'origin' => 'privacy-multi-active',
					'target' => 'privacy-multi-option',
					'show'   => 'yes',
				),
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
		if ( WP_DEBUG || $all ) {
			$layout['autoupdate'] = array(
				'default' => 'yes',
				'label'   => __( 'WP Updates', 'tcc-privacy' ),
				'text'    => __( 'Allow/prevent WordPress automatic updates.  You should not need to set this at all.', 'tcc-privacy' ),
				'postext' => __( 'This will only stop automatic updates, it will not make them happen.', 'tcc-privacy' ),
				'help'    => __( 'WordPress generally does the right thing here.  I recommend the default Allow.', 'tcc-privacy' ),
				'render'  => 'radio',
				'source'  => array(
					'yes'  => __( 'Allow WordPress to perform automatic updates.', 'tcc-privacy' ),
					'core' => __( 'Core automatic updates only.', 'tcc-privacy' ),
					'no'   => __( 'Prevent WordPress from doing any automatic updates.', 'tcc-privacy' ),
				),
				'extra_html' => $extra_html,
			);
		}
		$layout['plugindata'] = array(
			'label'   => __( 'Plugin Data', 'tcc-privacy' ),
			'text'    => __( 'Settings for the Privacy My Way plugin.', 'tcc-privacy' ),
			'render'  => 'title',
		);
#		if ( WP_DEBUG || $all ) {
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
#		} //*/
		$layout['deledata'] = array(
			'default' => ( WP_DEBUG ) ? 'nodelete' : 'uninstall',
			'label'   => __( 'Data Deletion', 'tcc-privacy' ),
			'text'    => __( 'Control when plugin data is deleted.', 'tcc-privacy' ),
			'render'  => 'radio',
			'source'  => array(
				'deactive'  => __( 'Deactivation of the plugin.', 'tcc-privacy' ),
				'uninstall' => __( 'Deletion of the plugin.', 'tcc-privacy' ),
				'nodelete'  => __( 'Always retain the plugin data.', 'tcc-privacy' ),
			),
		);
		return apply_filters( "tcc_options_layout_{$this->base}", $layout );
	}


	/**  Plugin functions  **/

	public function get_plugin_defaults( ) {
		$options = $this->get_option( 'plugin_list', array() );
		$preset  = $this->get_option( 'install_default', 'yes' );
		foreach( $this->plugins as $key => $plugin ) {
			if ( ! isset( $options[ $key ] ) || empty( $options[ $key ] ) ) {
				#	Load missing items with the default value, with new actives getting an automatic 'yes'
				$options[ $key ] = ( in_array( $key, $this->active ) ) ? 'yes' : $preset;
				if ( strpos( $key, 'privacy-my-way' ) === 0 ) {
					$options[ $key ] = 'no';  #  Set our own initial value
				}
			}
		}
		return $options;
	}

	private function get_plugin_list() {
		$plugin_list  = array();
		$title_label  = __( 'Plugin website', 'tcc-privacy' );
		$author_label = __( 'Plugin author', 'tcc-privacy' );
		$active   = sprintf( '<span class="pmw-plugin-active">(%s)</span>',   esc_html__( 'active',   'tcc-privacy' ) );
		$inactive = sprintf( '<span class="pmw-plugin-inactive">(%s)</span>', esc_html__( 'inactive', 'tcc-privacy' ) );
		$format   = esc_html_x( '%1$s %2$s by %3$s', '1: plugin title, 2: plugin active/inactive status, 3: plugin author name', 'tcc-privacy' );
		foreach ( $this->plugins as $key => $plugin ) {
			$title_attrs = array(
				'href'   => $plugin['PluginURI'],
				'target' => $key,
				'title'  => $title_label,
				'aria-label' => $title_label,
			);
			$title  = '<a ' . $this->library->get_apply_attrs( $title_attrs ) . '>' . esc_html( $plugin['Name'] ) . '</a>';
			$status = ( in_array( $key, $this->active ) ) ? $active : $inactive;
			$author_attrs = array(
				'href'   => $plugin['AuthorURI'],
				'target' => sanitize_title( $plugin['Author'] ),
				'title'  => $author_label,
				'aria-label' => $author_label,
			);
			$author = '<a ' . $this->library->get_apply_attrs( $author_attrs ) . '>' . esc_html( $plugin['Author'] ) . '</a>';
			$plugin_list[ $key ] = sprintf( $format, $title, $status, $author );
		}
		return $plugin_list;
	}


	/**  Theme functions  **/

	private function get_theme_defaults() {
		$options = $this->get_option( 'theme_list', array() );
		$preset  = $this->get_option( 'install_default', 'yes' );
		foreach( $this->themes as $key => $theme ) {
			if ( ! isset( $options[ $key ] ) || empty( $options[ $key ] ) ) {
				$options[ $key ] = ( stripos( $key, 'twenty' ) === false ) ? $preset : 'yes';
			}
		}
		return $options;
	}

	private function get_theme_list() {
		$theme_list   = array();
		$theme_label  = __( 'Theme website', 'tcc-privacy' );
		$author_label = __( 'Theme author', 'tcc-privacy' );
		$format = esc_html_x( '%1$s by %2$s', '1: Theme title, 2: Author name', 'tcc-privacy' );
		foreach( $this->themes as $key => $theme ) {
			if ( strpos( $key, 'twenty' ) === 0 ) {
				continue;  #  Do not filter wordpress themes
			}
			$title_attrs = array(
				'href'   => $theme->get( 'ThemeURI' ),
				'target' => $key,
				'title'  => $theme_label,
				'aria-label' => $theme_label,
			);
			$title  = '<a ' . $this->library->get_apply_attrs( $title_attrs ) . '>' . esc_html( $theme->get( 'Name' ) ) . '</a>';
			$author_attrs = array(
				'href'   => $theme->get( 'AuthorURI' ),
				'target' => sanitize_title( $theme->get( 'Author' ) ),
				'title'  => $author_label,
				'aria-label' => $author_label,
			);
			$author = '<a ' . $this->library->get_apply_attrs( $author_attrs ) . '>' . esc_html( $theme->get( 'Author' ) ) . '</a>';
			$theme_list[ $key ] = sprintf( $format, $title, $author );
		}
		return $theme_list;
	}

	private function get_option( $option, $value = '' ) {
		if ( empty( $this->options ) ) {
			$this->options = get_option( 'tcc_options_privacy-my-way', array() );
		}
		if ( isset( $this->options[ $option ] ) ) {
			$value = $this->options[ $option ];
		}
		return $value;
	}

	protected function customizer_data() {
		$data = array(
			array(
			),
		);
		return apply_filters( "fluid_{$this->base}_customizer_data", $data );
	}

}
