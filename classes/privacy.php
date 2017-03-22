<?php
/*
 *
 *  sources: https://core.trac.wordpress.org/ticket/16778
 *           https://gist.github.com/mattyrob/2e492e5ecb92233eb307f7efd039c121
 *           https://github.com/dannyvankooten/my-precious
 *
 *  Multisite code is untested
 *  Translation code is ... well, there isn't any
 *
 *  Note:  if $this->debug is set to true, then it may fill up your log file... ;-)
 */

defined( 'ABSPATH' ) || exit;


class Privacy_My_Way {

	protected $debug   =  WP_DEBUG;    #  set to true to enable logging
	protected $force   =  false;       #  can be used to force a logging entry on a one time basis
	protected $form    =  null;
	protected $logging = 'log_entry';  #  set to a valid logging function - must be able to accept a variable number of parameters
	protected $options;

	use PMW_Trait_Singleton;

	protected function __construct( $args = array() ) {
		$this->get_options();
		if ( $this->debug && $args ) {
			$this->run_tests( $args );
		} else {
			if ( $this->options ) {  #  opt-in only
				#	These first two filters are multisite only
				add_filter( 'pre_site_option_blog_count', array( $this, 'pre_site_option_blog_count' ), 10, 3 );
				add_filter( 'pre_site_option_user_count', array( $this, 'pre_site_option_user_count' ), 10, 3 );
				add_filter( 'pre_http_request',           array( $this, 'pre_http_request' ),            2, 3 );
				add_filter( 'http_request_args',          array( $this, 'http_request_args' ),          11, 2 );
			}
		}
		$this->logging( $this );
	}

	protected function get_options() {
		$options = get_option( 'tcc_options_privacy', array() );
		if ( ! $options ) {
			$this->form = new PMW_Options_Privacy;
			$options = $privacy->get_privacy_defaults();
			update_option( 'tcc_options_privacy', $options );
		}
		$this->options = $options;
		#	check logging option
		if ( is_string( $this->logging ) ) {
			if ( ! function_exists( $this->logging ) ) {
				$this->logging = $this->debug = false;
			}
		} else if ( is_array( $this->logging ) ) {
			if ( ! method_exists( $this->logging[0], $this->logging[1] ) ) {
				$this->logging = $this->debug = false;
			}
		} else {
			$this->logging = $this->debug = false;
		}
	}

	#	Filter triggered on multisite installs, called internally for single site
	public function pre_site_option_blog_count( $count, $option, $network_id = 1 ) {
		if ( isset( $this->options['blogs'] ) && ( $this->options['blogs'] === 'no' ) ) {
			$count = 1;
		}
		return $count;
	}

	#	Filter triggered on multisite installs, called internally for single site
	public function pre_site_option_user_count( $count, $option, $network_id = 1 ) {
		$privacy = $this->options['users'];
		if ( $privacy ) {
			$saved = $count;
			#	if $count has a value, then use it, otherwise get our own count
			$users = ( $count ) ? $count : $this->get_user_count();
			switch( $privacy ) {
				case 'all':
					$count = false;
					break;
				case 'some':
					$count = random_int( 1, $users );
					break;
				case 'one':
					$count = 1;
					break;
				case 'many':
					$count = random_int( 1, ( $users * 10 ) );
					break;
				default:
			}
			$this->logging(
				'setting: ' . $this->options['users'],
				"  param: $saved",
				"  users: $users",
				" calced: $count",
				" source: $option",
				"network: $network_id"
			);
		}
		return $count;
	}

	private function get_user_count() {
		$count = 1;
		#	wp-includes/update.php
		if ( is_multisite() ) {
			$count = get_user_count();
		} else {
			$users = count_users();
			$count = $users['total_users'];
		}
		return $count;
	}

	public function http_request_args( $args, $url ) {
		#	only act on requests to api.wordpress.org
		if ( stripos( $url, '://api.wordpress.org/' ) === false ) {
			return $args;
		}
		$args = $this->strip_site_url( $args );
		$args = $this->filter_plugins( $url, $args );
		$args = $this->filter_themes(  $url, $args );
		return $args;
	}

	public function pre_http_request( $preempt, $args, $url ) {
		#	check if already preempted or if we have been here before
		if ( $preempt || isset( $args['_pmw_privacy_filter'] ) ) {
			return $preempt;
		}
		#	only act on requests to api.wordpress.org
		if ( ( stripos( $url, '://api.wordpress.org/core/version-check/'   ) === false )
			&& ( stripos( $url, '://api.wordpress.org/plugins/update-check/' ) === false )
			&& ( stripos( $url, '://api.wordpress.org/themes/update-check/'  ) === false )
			//  FIXME:  I have no way of testing this or knowing what the object looks like.
			&& ( stripos( $url, '://api.wordpress.org/translations/'         ) === false )
			) {
			return $preempt;
		}
		$url  = $this->filter_url( $url );
		$args = $this->strip_site_url( $args );
		$args = $this->filter_plugins( $url, $args );
		$args = $this->filter_themes( $url, $args );
		#	make request
		$args['_pmw_privacy_filter'] = true;
		$response = wp_remote_request( $url, $args );
		//	response really seems to have a lot of duplicated data in it.
		if ( is_wp_error( $response ) ) {
			$this->force = true;  #  Log it.
			$this->logging( $url, $response );
		} else {
			$body = trim( wp_remote_retrieve_body( $response ) );
			$body = json_decode( $body, true );
			$this->logging( $body );
		}
		return $response;
	}

	/**
	 *  @brief  Strip site URL from headers & user-agent.
	 *
	 *		I would consider including the url in user-agent as a matter of courtesy.
	 *		Besides, what is the point in not giving them your website url?  Don't
	 *		you want more people to see it?  Privacy does not mean you shouldn't say
	 *		hi to your neighbors. I really think this whole header section is a moot
	 *		point.  Also, what if the devs at wordpress.org have decided to cause the
	 *		version check/update to fail because of no url?
	 *
	 */
	protected function strip_site_url( $args ) {
		if ( ! isset( $args['_pmw_privacy_strip_site'] ) || ( ! $args['_pmw_privacy_strip_site'] ) ) {
			if ( $this->options['blog'] === 'no' ) {
				if ( isset( $args['headers']['wp_blog'] ) ) {
					unset( $args['headers']['wp_blog'] );
				}
				if ( isset( $args['user-agent'] ) ) {
					$args['user-agent'] = sprintf( 'WordPress/%s', $GLOBALS['wp_version'] );
				}
				#	Hmmm, really?
				if ( isset( $args['headers']['user-agent'] ) ) {
					$args['headers']['user-agent'] = sprintf( 'WordPress/%s', $GLOBALS['wp_version'] );
					$this->logging( 'header:user-agent has been seen.' );
				}
				#	Anybody seen this?
				if ( isset( $args['headers']['User-Agent'] ) ) {
					$args['headers']['User-Agent'] = sprintf( 'WordPress/%s', $GLOBALS['wp_version'] );
					$this->logging( 'header:User-Agent has been seen.' );
				}
				#	Why remove this? I have not seen it...
				if ( isset( $args['headers']['Referer'] ) ) {
					unset( $args['headers']['Referer'] );
					$this->logging( 'headers:Referer has been deleted.' );
				}
			}
			if ( isset( $this->options['install'] ) && ( $this->options['install'] === 'no' ) ) {
				if ( isset( $args['headers']['wp_install'] ) ) {
					if ( $this->options['blog'] === 'no' ) {
						unset( $args['headers']['wp_install'] );
					} else { // FIXME:  not sure this is a good idea, need more data
						$args['headers']['wp_install'] = $args['headers']['wp_blog'];
					}
				}
			}
			$args['_pmw_privacy_strip_site'] = true;
		} #else { $this->logging( 'already been here', $args ); }
		return $args;
	}

	protected function filter_plugins( $url, $args ) {
		if ( stripos( $url, '://api.wordpress.org/plugins/update-check/' ) !== false ) {
			if ( ! isset( $args['_pmw_privacy_filter_plugins'] ) || ( ! $args['_pmw_privacy_filter_plugins'] ) ) {
				if ( ! empty( $args['body']['plugins'] ) ) {
					$plugins = json_decode( $args['body']['plugins'] );
#					$this->logging( $url, $plugins );
					$this->logging( $args );
					$new_set = new stdClass;
					if ( $this->options['plugins'] === 'none' ) {
						$plugins = $new_set;
					} else if ( $this->options['plugins'] === 'active' ) {
						foreach( $plugins->plugins as $plugin => $info ) {
							if ( in_array( $plugin, (array)$plugins->active ) ) {
								$new_set->$plugin = $info;
							}
						}
						$plugins->plugins = $new_set;
					} else if ( $this->options['plugins'] === 'filter' ) {
						$plugin_filter   = $this->options['plugin_list'];
						$installed_list  = get_plugins();
						foreach ( $plugin_filter as $plugin => $status ) {
							if ( ( $status === 'no' ) ) { # || ( $plugin === 'privacy-my-way' ) ) {
								if ( isset( $plugins->plugins->$plugin ) ) {
									unset( $plugins->plugins->$plugin );
								}
							}
							if ( ( $this->options['install_default'] === 'no' ) && isset( $installed_list[ $plugin ] ) ) {
								unset( $installed_list[ $plugin ] );
							}
						}
						#	Check for newly installed plugins
						if ( ( $this->options['install_default'] === 'no' ) && $installed_list ) {
							foreach( $installed_list as $key => $plugin ) {
								if ( isset( $plugins->plugins->$key ) ) {
									unset( $plugins->plugins->$plugin );
								}
							}
						}
						#	Rebuild active plugins object
						$count = 1;
						foreach( (array)$plugins->active as $key => $plugin ) {
							if ( isset( $plugins->plugins->$plugin ) ) {
								$new_set->$count = $plugin;
								$count++;
							}
						}
						$plugins->active = $new_set;
					}
					$this->logging( 'plugins:  ' . $this->options['plugins'], $plugins );
					$args['body']['plugins'] = wp_json_encode( $plugins );
					$args['_pmw_privacy_filter_plugins'] = true;
				}
			} #else { $this->logging( 'already been here', $args ); }
		}
		return $args;
	}

	protected function filter_themes( $url, $args ) {
		if ( stripos( $url, '://api.wordpress.org/themes/update-check/' ) !== false ) {
			if ( ! isset( $args['_pmw_privacy_filter_themes'] ) || ( ! $args['_pmw_privacy_filter_themes'] ) ) {
				if ( ! empty( $args['body']['themes'] ) ) {
					$themes = json_decode( $args['body']['themes'] );
					$this->logging( $url, $themes );
					#	Report no themes installed
					if ( $this->options['themes'] === 'none' ) {
						$themes = new stdClass;
						$themes->active = '';
						$themes->themes = new stdClass;
					#	Report only active theme, plus parent if active is child
					} else if ( $this->options['themes'] === 'active' ) {
						$installed = new stdClass;
						$active    = $themes->active;
						$installed->$active = $themes->themes->$active;
						#	Check for child theme
						if ( $installed->$active->Template !== $installed->$active->Stylesheet ) {
							$parent = $installed->$active->Template;
							$installed->$parent = $themes->themes->$parent;
						}
						$themes->themes = $installed;
					#	Filter themes
					} else if ( $this->options['themes'] === 'filter' ) {
						$theme_filter  = $this->options['theme_list'];
						#	Store site active theme
						$active_backup = $themes->active;
						$this->logging( 0, 'active theme:  ' . $active_backup );
						#	Loop through our filter list
						foreach ( $theme_filter as $theme => $status ) {
							#	Is theme still installed?
							if ( isset( $themes->themes->$theme ) ) {
								#	Is the theme being filtered?
								if ( ( $status === 'no' ) ) {
									unset( $themes->themes->$theme );
									#	Is this the active theme?
									$active_backup = ( $active_backup === $theme ) ? '' : $active_backup;
									$this->logging( 0, 'unset theme:  ' . $theme, 'active theme:  ' . $active_backup );
								} else {
									#	Should a different active theme be reported?
									$active_backup = ( $active_backup ) ? $active_backup : $theme;
									$this->logging( 0, 'current theme:  ' . $theme, 'active theme:  ' . $active_backup );
								}
							} else {
								#	Is this the active theme?
								$active_backup = ( $active_backup === $theme ) ? '' : $active_backup;
							}
						}
						$themes->active = $active_backup;
						$this->logging( 0, 'calced active theme:  ' . $active_backup );
					}
					$this->logging( 'themes:  ' . $this->options['themes'], $themes );
					$args['body']['themes'] = wp_json_encode( $themes );
					$args['_pmw_privacy_filter_plugins'] = true;
				}
			} #else { $this->logging( 'already been here', $args ); }
		}
		return $args;
	}

	protected function filter_url( $url ) {
		$orig = $url;
		#$keys = array( 'php', 'locale', 'mysql', 'local_package', 'blogs', 'users', 'multisite_enabled', 'initial_db_version',);
		$url_array = wp_parse_url( $url );
		$this->logging( $url_array );
		#	Do we need to filter?
		if ( isset( $url_array['query'] ) ) {
			$arg_array = wp_parse_args( $url_array['query'] );
			$this->logging( $arg_array );
			if ( is_multisite() ) {
				#	I really think that fibbing on this is a bad idea, but my pro-choice stance dictates that I can't make other people's choices for them.
				if ( isset( $arg_array['multisite_enabled'] ) && ( $this->options['blogs'] === 'no' ) ) {
					$url = add_query_arg( 'multisite_enabled', '0', $url );
				}
			} else {
				#	Need this for single site. If multisite then these have already been filtered
				if ( isset( $arg_array['blogs'] ) ) {
					$blogs = $this->pre_site_option_blog_count( $arg_array['blogs'], 'pmw_blog_count' );
					$url   = add_query_arg( 'blogs', $blogs, $url );
				}
				if ( isset( $arg_array['users'] ) ) {
					$users = $this->pre_site_option_user_count( $arg_array['users'], 'pmw_user_count' );
					$url   = add_query_arg( 'users', $users, $url );
				}
			}
		}
		$this->logging( $orig, $url );
		return $url;
	}


	/*  Debugging  */

	protected function logging() {
		if ( $this->logging && ( $this->debug || $this->force ) ) {
			call_user_func_array( $this->logging, func_get_args() );
		}
		$this->force = false;
	}

	private function run_tests( $args ) {
		if ( isset( $args['themes'] ) ) {
			$test_data = $args['themes'];
			$themes = $this->filter_themes( $test_data['url'], $test_data['args'] );
			$this->logging( $test_data, $themes );
		}
	}


} # end of class Privacy_My_Way
