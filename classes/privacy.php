<?php
/**
 *
 *
 *  Multisite code is untested
 *  Translation code is ... well, there isn't any
 *
 *  Note:  if $this->logging_debug is set to true, then it may fill up your log file... ;-)
 *
 * @package Privacy_My_Way
 * @subpackage Plugin
 * @since 20170221
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2017, Richard Coffee
 * @link https://core.trac.wordpress.org/ticket/16778
 * @link https://gist.github.com/mattyrob/2e492e5ecb92233eb307f7efd039c121
 * @link https://github.com/dannyvankooten/my-precious
 * @link https://github.com/RichardCoffee/privacy-my-way/blob/master/classes/privacy.php
 */
defined( 'ABSPATH' ) || exit;


class Privacy_My_Way {


	/**
	 * @since 20170221
	 * @var array  Privacy options
	 */
	protected $options;

	/**
	 * @since 20170323
	 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/Logging.php
	 */
	use PMW_Trait_Logging;


	/**
	 *  Constructor method.
	 *
	 * @since 20170221
	 */
	public function __construct() {

		$this->options = $this->get_options();
		$this->logging_func  = array( $this, 'log' );
		$this->logging_debug = apply_filters( 'logging_debug_privacy', ( $this->options['logging'] === 'on' ) );

		if ( $this->options ) {  #  opt-in only

			add_filter( 'core_version_check_query_args', [ $this, 'core_version_check_query_args' ] );

			//  These next two filters are multisite only
			add_filter( 'pre_site_option_blog_count', [ $this, 'pre_site_option_blog_count' ], 10, 3 );
			add_filter( 'pre_site_option_user_count', [ $this, 'pre_site_option_user_count' ], 10, 3 );

			add_filter( 'http_headers_useragent',     [ $this, 'http_headers_useragent' ],     10, 2 );
			add_filter( 'pre_http_request',           [ $this, 'pre_http_request' ],            2, 3 );
			add_filter( 'http_request_args',          [ $this, 'http_request_args' ],          11, 2 );

			add_filter( 'pre_set_site_transient_update_themes',  [ $this, 'themes_site_transient' ],  10, 2 );
			add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'plugins_site_transient' ], 10, 2 );
			add_filter( 'site_transient_update_plugins',         [ $this, 'plugins_site_transient' ], 10, 2 );

		}
		$this->logg( $this );
	}

	/**
	 *  Retrieve the plugin options.
	 *
	 * @since 20170221
	 * @return array  The plugin options.
	 */
	protected function get_options() {
		$options = get_option( 'tcc_options_privacy-my-way', array() );
		if ( empty( $options ) ) {
			$privacy = new PMW_Options_Privacy( false );
			$options = $privacy->get_default_options();
			update_option( 'tcc_options_privacy-my-way', $options );
		}
		add_filter( 'logging_debug_privacy', function( $debug = false ) {
			return ( array_key_exists( 'logging', $this->options ) && ( $this->options['logging'] === 'on' ) ) ? true : (bool) $debug;
		} );
		return $options;
	}

	/**
	 *  Check some of the url query arguments.
	 *
	 * @since 20180303
	 * @param  array $args Incoming query args.
	 * @return array       Modified query args.
	 */
	public function core_version_check_query_args( $args ) {
		$args['blogs'] = $this->pre_site_option_blog_count( $args['blogs'], null );
		$args['users'] = $this->pre_site_option_user_count( $args['users'], null );
		if ( $args['blogs'] === 1 ) {
			$args['multisite_enabled'] = 0;
		}
		return $args;
	}

	/**
	 *  Filters the blog count, triggered on multisite installs, called internally for single site.
	 *
	 * @since 20170221
	 * @param integer $count       Number of blogs for the site/multi-site.
	 * @param string  $option      Name of the option being filtered - 'blog_count'.
	 * @param integer $network_id  Number of the site, defaults to 1 for single site.
	 * @return integer             Modified site count.
	 */
	public function pre_site_option_blog_count( $count, $option, $network_id = 1 ) {
		if ( array_key_exists( 'blogs', $this->options ) && ( $this->options['blogs'] === 'no' ) ) {
			$count = 1;
		}
		return $count;
	}

	/**
	 *  Filters the user count, triggered on multisite installs, called internally for single site.
	 *
	 * @since 20170221
	 * @param integer $count       Wordpress user count for the site.
	 * @param string  $option      Name of the option being filtered - 'user_count'
	 * @param integer $network_id  Number of the site, defaults to 1 for single site.
	 * @return integer             Modified user count.
	 */
	public function pre_site_option_user_count( $count, $option, $network_id = 1 ) {
		static $called = false;  //  recursion flag
		if ( $called && $count ) return $count;
		$privacy = $this->options['users'];
		if ( $privacy ) {
			$original = $count;
			//  If $count has a value, then use it, otherwise get our own count.
			$users = ( $count ) ? $count : $this->get_user_count( $network_id );
			switch( $privacy ) {
				case 'all':
					$count = false;
					break;
				case 'some':
					$count = wp_rand( 1, $users );
					break;
				case 'one':
					$count = 1;
					break;
				case 'many':
					$count = wp_rand( 1, ( $users * 10 ) );
					break;
				default:
			}
			$this->logg(
				'setting: ' . $this->options['users'],
				compact( 'original', 'users', 'count', 'option', 'network_id' )
			);
		}
		$called = true;
		return $count;
	}

	/**
	 *  Get the user count.
	 *
	 * @since 20170309
	 * @return  The site user count.
	 */
	private function get_user_count( $network_id ) {
		$count = 1;
		//  wp-includes/update.php
		if ( is_multisite() ) {
			$count = get_user_count( $network_id );
		} else {
			$users = count_users();
			$count = $users['total_users'];
		}
		return $count;
	}

	/**
	 *  Removes the site url from the header user-agent string.
	 *
	 * @since 20170407
	 * @param string $string  What wordpress wants to know.
	 * @return string         What you want them to know.
	 */
	public function http_headers_useragent( $string ) {
		if ( $this->options['blog'] === 'no' ) {
			$string = 'WordPress/' . get_bloginfo( 'version' );
		}
		return $string;
	}

	/**
	 *  Filters the message information to be sent to wordpress.
	 *
	 * @since 20170221
	 * @param array  $args  The information wordpress wants.
	 * @param string $url   Wordpress server url.
	 * @return array        The information you allow wordpress to get.
	 */
	public function http_request_args( $args, $url ) {
		//  Only act on requests to api.wordpress.org
		if ( stripos( $url, '://api.wordpress.org/' ) === false ) {
			return $args;
		}
		$args = $this->strip_site_url( $args );
		$args = $this->filter_plugins( $args, $url );
		$args = $this->filter_themes(  $args, $url );
		$this->logg( $url, $args );
		return $args;
	}

	/**
	 *  Filters the HTTP request.
	 *
	 * @since 20170221
	 * @param mixed $preempt  If this has a value, then simply return it, because something else is going on.
	 * @param array $args     The body of the request.
	 * @param $url            The wordpress server url.
	 * @return ???            The response of the wordpress server for the request.
	 */
	public function pre_http_request( $preempt, $args, $url ) {
		//  Check if already preempted or if we have been here before
		if ( $preempt || array_key_exists( '_pmw_privacy_filter', $args ) ) {
			return $preempt;
		}
		$this->logg( 0, 'url: ' . $url );
		//  Do we want to tell wordpress.org what browser is being used?
		if ( $this->options['browser'] === 'no' ) {
			if ( ! ( stripos( $url, '://api.wordpress.org/core/browse-happy' ) === false ) ) {
				return new WP_Error(
					'blocked-browser',
					__( 'Report of browser used blocked by Privacy My Way plugin.', 'privacy-my-way' )
				);
			}
		}
		//  Disable revealing location?
		if ( $this->options['location'] === 'no' ) {
			if ( ! ( stripos( $url, '://api.wordpress.org/events' ) === false ) ) {
				return new WP_Error(
					'blocked-location',
					__( 'Report of current location blocked by Privacy My Way plugin.', 'privacy-my-way' )
				);
			}
		}
		//  Only act on requests to api.wordpress.org
		if (  ( stripos( $url, '://api.wordpress.org/core/version-check/'   ) === false )
			&& ( stripos( $url, '://api.wordpress.org/plugins/update-check/' ) === false )
			&& ( stripos( $url, '://api.wordpress.org/themes/update-check/'  ) === false )
#			&& ( stripos( $url, '://api.wordpress.org/translations/'         ) === false )
			) {
			return $preempt;
		}

		$url  = $this->filter_url( $url );
		$args = $this->strip_site_url( $args );
		$args = $this->filter_plugins( $args, $url );
		$args = $this->filter_themes( $args, $url );
		//  Make the request.
		$args['_pmw_privacy_filter'] = true;
		$response = wp_remote_request( $url, $args );	//  response really seems to have a lot of duplicated data in it.
		if ( is_wp_error( $response ) ) {
			$this->logging_force = true;  //  Log the error.
			$this->logg( 'response error', $url, $response );
		} else {
			$body = trim( wp_remote_retrieve_body( $response ) );
			$this->logg( $url, $args, 'response body', json_decode( $body, true ) );
		}
		return $response;
	}

	/**
	 *  @brief  Strip site URL from headers & user-agent.
	 *
	 *  I would consider including the url in user-agent as a matter of courtesy.
	 *  Besides, what is the point in not giving them your website url?  Don't
	 *  you want more people to see it?  Privacy does not mean you shouldn't say
	 *  hi to your neighbors. I really think this whole header section is a moot
	 *  point.  Also, what if the devs at wordpress.org decide to cause the
	 *  version check/update to fail because of no url?
	 *
	 *  On the other hand, when running a local site on my own computer, I may not
	 *  necessarily want wordpress to know all about it.
	 *
	 * @since 20170221
	 * @param array $args  Header strings for the request.
	 * @return array       Modified header strings.
	 */
	protected function strip_site_url( $args ) {
		if ( ! array_key_exists( '_pmw_privacy_strip_site', $args ) || ( ! $args['_pmw_privacy_strip_site'] ) ) {
			if ( $this->options['blog'] === 'no' ) {
				if ( array_key_exists( 'wp_blog', $args['headers'] ) ) {
					$args['headers']['wp_blog'] = $args['headers']['wp_install'];
				}
				if ( array_key_exists( 'user-agent', $args ) ) {
					$args['user-agent'] = $this->http_headers_useragent( $args['user-agent'] );
				}
				//  Next three checks taken from resources.  I have not seen these in testing...
				if ( array_key_exists( 'user-agent', $args['headers'] ) ) {
					$args['headers']['user-agent'] = $this->http_headers_useragent( $args['headers']['user-agent'] );
					$this->logg( 'header:user-agent has been seen.' );
				}
				//  Anybody seen this?
				if ( array_key_exists( 'User-Agent', $args['headers'] ) ) {
					$args['headers']['User-Agent'] = $this->http_headers_useragent( $args['headers']['User-Agent'] );
					$this->logg( 'header:User-Agent has been seen.' );
				}
				//  I have not seen it...  All three of these are going to go away at some point.
				if ( array_key_exists( 'Referer', $args['headers'] ) ) {
					unset( $args['headers']['Referer'] );
					$this->logg( 'headers:Referer has been deleted.' );
				}
			}
			if ( array_key_exists( 'install', $this->options ) && ( $this->options['install'] === 'no' ) ) {
				if ( array_key_exists( 'wp_install', $args['headers'] ) ) {
					if ( $this->options['blog'] === 'no' ) {
						unset( $args['headers']['wp_install'] );
					} else if ( array_key_exists( 'wp_blog', $args['headers'] ) ) {
						$args['headers']['wp_install'] = $args['headers']['wp_blog'];
					}
				}
			}
			$args['_pmw_privacy_strip_site'] = true;
		}
		return $args;
	}


	/***   Plugins   ***/

	/**
	 *  Filter the plugins being reported to wordpress.
	 *
	 * @since 20170221
	 * @param array  $args  The message body.
	 * @param string $url   Wordpress server url.
	 * @return array        Modified message body to send to wordpress.
	 */
	protected function filter_plugins( $args, $url ) {
		if ( stripos( $url, '://api.wordpress.org/plugins/update-check/' ) !== false ) {
			if ( ! empty( $args['body']['plugins'] ) ) {
				$plugins = json_decode( $args['body']['plugins'], true );
				switch ( $this->options['plugins'] ) {
					case 'none':
						$plugins = array();
						break;
					case 'active':
						// If the index does not exist, then the array is already the active plugins
						if ( array_key_exists( 'plugins', $plugins ) ) {
							$plugins = $this->plugins_option_active( $plugins );
						}
						break;
					case 'filter':
						$plugins = $this->plugins_option_filter( $plugins );
						break;
					case 'all':
						break;
					default:
						pmw(1)->log('ERROR: option - ' . $this->options['plugins'], $this );
				}
				$this->logg( 'plugins option:  ' . $this->options['plugins'], $plugins );
				$args['body']['plugins'] = wp_json_encode( $plugins );
			}
		}
		return $args;
	}

	/**
	 *  Report only the active plugins to wordpress.
	 *
	 * @since 20170330
	 * @param array $plugins  Plugins to filter.
	 * @return array          Filtered plugin list.
	 */
	protected function plugins_option_active( $plugins ) {
		$active = array();
		foreach( $plugins['plugins'] as $plugin => $info ) {
			if ( in_array( $plugin, $plugins['active'] ) ) {
				$active[ $plugin ] = $info;
			}
		}
		return $active;
	}

	/**
	 *  Filter the reported plugins.
	 *
	 * @since 20170330
	 * @param array $plugins  List of installed plugins.
	 * @return array          Filtered plugin list.
	 */
	protected function plugins_option_filter( $plugins ) {
		$installed = get_plugins();
		foreach ( $this->options['plugin_list'] as $plugin => $status ) {
			if ( ( $status === 'no' ) ) {
				if ( array_key_exists( $plugin, $plugins['plugins'] ) ) {
					unset( $plugins['plugins'][ $plugin ] );
				}
			}
			if ( array_key_exists( $plugin, $installed ) ) {
				unset( $installed[ $plugin ] );
			}
		}
		//  Check for newly installed plugins.
		if ( $installed && ( $this->options['install_default'] === 'no' ) ) {
			foreach( $installed as $key => $plugin ) {
				if ( array_key_exists( $key, $plugins['plugins'] ) ) {
					unset( $plugins['plugins'][ $key ] );
				}
			}
		}
		//  Rebuild active plugins list
		$count  = 1;
		$active = array();
		foreach( $plugins['active'] as $key => $plugin ) {
			if ( array_key_exists( $plugin, $plugins['plugins'] ) ) {
				$active[ $count++ ] = $plugin;
			}
		}
		$plugins['active'] = $active;
		return $plugins;
	}

	/**
	 *  Filter for the site transient.
	 *
	 * @since 20170425
	 * @param object $value  Original value
	 * @return object        Filtered value
	 */
	public function plugins_site_transient( $value, $transient ) {
		if ( pmw()->was_called_by('get_site_transient') === false ) {
			if ( $this->options['plugins'] === 'filter' ) {
				foreach( $this->options['plugin_list'] as $plugin => $state ) {
					if ( $state === 'no' ) {
						if ( property_exists( $value, 'checked' ) && array_key_exists( $plugin, $value->checked ) ) {
							unset( $value->checked[ $plugin ] );
						}
						if ( property_exists( $value, 'response' ) && array_key_exists( $plugin, $value->response ) ) {
							unset( $value->response[ $plugin ] );
						}
						if ( property_exists( $value, 'no_update' ) && array_key_exists( $plugin, $value->no_update ) ) {
							unset( $value->no_update[ $plugin ] );
						}
					}
				}
			}
		}
		return $value;
	}


	/**  Themes  **/

	/**
	 *  Filter the reported themes.
	 *
	 * @since 20170221
	 * @param array  $args  The message body.
	 * @param string $url   Wordpress server url.
	 * @return array        Modified message body.
	 */
	protected function filter_themes( $args, $url ) {
		if ( stripos( $url, '://api.wordpress.org/themes/update-check/' ) !== false ) {
			if ( ! array_key_exists( '_pmw_privacy_filter_themes', $args ) || ( ! $args['_pmw_privacy_filter_themes'] ) ) {
				if ( ! empty( $args['body']['themes'] ) ) {
					$themes = json_decode( $args['body']['themes'], true );
					$this->logg( $url, $themes );
					switch ( $this->options['themes'] ) {
						case 'none':
							$themes = array(
								'active' => '',
								'themes' => array(),
							);
							break;
						case 'active':
							$themes['themes'] = $this->themes_option_active( $themes );
							break;
						case 'filter':
							$themes = $this->themes_option_filter( $themes );
							break;
						default:
					}
					$this->logg( 'themes:  ' . $this->options['themes'], $themes );
					$args['body']['themes'] = wp_json_encode( $themes );
					$args['_pmw_privacy_filter_themes'] = true;
				}
			} #else { $this->logg( 'already been here', $args ); }
		}
		return $args;
	}

	/**
	 *  Filter for the active theme.
	 *
	 * @since 20170330
	 * @param array $themes  List of installed themes.
	 * @return array         List with modified active theme.
	 */
	protected function themes_option_active( $themes ) {
		$installed = array();
		$active    = $themes['active'];
		$installed[ $active ] = $themes['themes'][ $active ];
		//  Check for child theme.
		if ( $installed[ $active ]['Template'] !== $installed[ $active ]['Stylesheet'] ) {
			$parent = $installed[ $active ]['Template'];
			$installed[ $parent ] = $themes['themes'][ $parent ];
		}
		return $installed;
	}

	/**
	 *  Filter the themes list.
	 *
	 * @since 20170330
	 * @param array $themes Installed themes.
	 * @return array        List of filtered themes.
	 */
	protected function themes_option_filter( $themes ) {
		$filter = $this->options['theme_list'];
		//  Store site active theme
		$active = $themes['active'];
		//  Loop through our filter list
		foreach ( $filter as $theme => $status ) {
			//  Is theme still installed?
			if ( array_key_exists( $theme, $themes['themes'] ) ) {
				//  Is the theme being filtered?
				if ( ( $status === 'no' ) ) {
					unset( $themes['themes'][ $theme ] );
					//  Is this the active theme?
					$active = ( $active === $theme ) ? '' : $active;
				} else {
					//  Do we need to set a new active theme?
					$active = ( $active ) ? $active : $theme;
				}
			} else {  //  Theme has been deleted
				//  Is this the active theme?
				$active = ( $active === $theme ) ? '' : $active;
			}
		}
		//  Do we need to set a new active theme?
		if ( empty( $active ) ) {
			$keys   = array_keys( $themes['themes'] );
			$active = $keys[0];
		}
		$themes['active'] = $active;
		return $themes;
	}

	/**
	 *  Filter the themes site transient.
	 *
	 * @since 20170418
	 * @param object $value      Transient value.
	 * @param string $transient  Transient name.
	 * @return object            Modified value.
	 */
	public function themes_site_transient( $value, $transient ) {
		foreach( $this->options['theme_list'] as $theme => $state ) {
			if ( $state === 'no' ) {
				if ( array_key_exists( $theme, $value->checked ) ) {
					unset( $value->checked[ $theme ] );
				}
			}
		}
		return $value;
	}

	/**
	 *  Filter the url parameters.
	 *
	 * @since 20170221
	 * @param string $url  The wordpress server url.
	 * @return string      Modified url.
	 */
	protected function filter_url( $url ) {
		$original = $url;
		#$keys = array( 'php', 'locale', 'mysql', 'local_package', 'blogs', 'users', 'multisite_enabled', 'initial_db_version',);
		$url_array = wp_parse_url( $url );
		$this->logg( $url_array );
		//  Do we need to filter?
		if ( array_key_exists( 'query', $url_array ) ) {
			$arg_array = wp_parse_args( $url_array['query'] );
			$this->logg( $arg_array );
			if ( is_multisite() ) {
				//  I really think that fibbing on this is a bad idea, but my pro-choice stance dictates that I can't make other people's choices for them.
				if ( array_key_exists( 'multisite_enabled', $arg_array ) && ( $this->options['blogs'] === 'no' ) ) {
					$url = add_query_arg( 'multisite_enabled', '0', $url );
				}
			} else {
				//  Need this for single site. If multisite then these have already been filtered
				if ( array_key_exists( 'blogs', $arg_array ) ) {
					$blogs = $this->pre_site_option_blog_count( $arg_array['blogs'], 'pmw_blog_count' );
					$url   = add_query_arg( 'blogs', $blogs, $url );
				}
				if ( array_key_exists( 'users', $arg_array ) ) {
					$users = $this->pre_site_option_user_count( $arg_array['users'], 'pmw_user_count' );
					$url   = add_query_arg( 'users', $users, $url );
				}
			}
			$this->logg( compact( 'original', 'url' ) );
		}
		return $url;
	}


	/*  Debugging  */

	/**
	 *  A test function.
	 *
	 * @since 20170321
	 * @param array $args  Arguements to run tests on.
	 */
	public function run_tests( $args ) {
		if ( array_key_exists( 'plugins', $args ) ) {
			$test_data = $args['plugins'];
			$plugins = $this->filter_plugins( $test_data['args'], $test_data['url'] );
			$this->logg( $test_data, $plugins );
		}
		if ( array_key_exists( 'themes', $args ) ) {
			$test_data = $args['themes'];
			$themes = $this->filter_themes( $test_data['args'], $test_data['url'] );
			$this->logg( $test_data, $themes );
		}
	}


}
