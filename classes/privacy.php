<?php
/*
 *
 *  sources: https://core.trac.wordpress.org/ticket/16778
 *           https://gist.github.com/mattyrob/2e492e5ecb92233eb307f7efd039c121
 *           https://github.com/dannyvankooten/my-precious
 */

defined('ABSPATH') || exit;


class Privacy_My_Way {

	protected $options;

	private $blog_count_done = false; # debug symbol
	private $user_count_done = false; # debug symbol

	use TCC_Trait_Singleton;

	protected function __construct() {
		$this->get_options();
		#	These filters are multisite only
		add_filter( 'pre_site_option_blog_count', array( $this, 'pre_site_option_blog_count' ),     10, 3 );
		add_filter( 'pre_site_option_user_count', array( $this, 'pre_site_option_user_count' ),     10, 3 );
		#	This filter has never gotten called in my tests, but I am not running multisite so...
		add_filter( 'http_request_args',          array( $this, 'http_request_args' ),              11, 2 );
		#	This seems to be the main workhorse, at least for single site installation
		add_filter( 'pre_http_request',           array( $this, 'pre_http_request' ),                2, 3 );
log_entry($this);
	}

	protected function get_options() {
		$options = get_option( 'tcc_options_privacy' );
		if ( ! $options ) {
			// FIXME
		}
		$this->options = $options;
	}

	public function pre_site_option_blog_count( $count, $option, $network_id ) {
		if ( $this->options['blogs'] === 'no' ) {
			$count = 1;
		}
		return $count;
	}

	public function pre_site_option_user_count( $count, $option, $network_id ) {
		$privacy = $this->options['users'];
		if ( $privacy ) {
			#	if $count has a value, then use it.
			$users = ($count) ? $count : get_user_count();
			switch( $privacy ) {
				case 'all':
					$count = false;
				case 'some':
					$count = max( 1, intval( ( $users / rand(1, 10) ), 10 ) );
				case 'one':
					$count = 1;
				case 'many':
					$count = rand( 1, ( $users * 10 ) );
				default:
			}
		}
		return $count;
	}

	public function http_request_args( $args, $url ) {
		#	only act on requests to api.wordpress.org
		if ( stripos( $url, '://api.wordpress.org/' ) !== false ) {
			return $args;
		}
#		$args = $this->strip_site_url( $args );
		$temp = $this->strip_site_url( $args );
#| |  $args = $this->filter_plugins( $url, $args );
		$temp = $this->filter_plugins( $url, $temp );
#		$args = $this->filter_themes( $url, $args );
		$temp = $this->filter_themes( $url, $temp );
log_entry($url,$temp);
		return $args;
	}

	public function pre_http_request( $preempt, $args, $url ) {
		#	check if we have been here before
		if ( $preempt || isset( $args['_privacy_filter'] ) ) {
			return $preempt;
		}
		#	only act on requests to api.wordpress.org
		if  ( ( stripos( $url, '://api.wordpress.org/core/version-check/'   ) === false )
			&& ( stripos( $url, '://api.wordpress.org/plugins/update-check/' ) === false )
			&& ( stripos( $url, '://api.wordpress.org/themes/update-check/'  ) === false )
			&& ( stripos( $url, '://api.wordpress.org/translations/'         ) === false )	#	outgoing never seems to have data - need to see what this looks like
			) {
			return $preempt;
		}
		$args = $this->strip_site_url( $args );
		$args = $this->filter_plugins( $url, $args );
		$args = $this->filter_themes( $url, $args );
#		$url  = $this->filter_url( $url );
		$temp = $this->filter_url( $url );
log_entry($url,$temp);
		#	make request
		$args['_privacy_filter'] = true;
log_entry($url,$args);
return $preempt;
		$result = wp_remote_request( $url, $args );
log_entry($result);
		return $result;
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
		if ( $this->options['blog'] === 'no' ) {
			if ( isset( $args['headers']['wp_blog'] ) ) {
				unset( $args['headers']['wp_blog'] );
			}
			if ( isset( $args['user-agent'] ) ) {
				$args['user-agent'] = sprintf( 'WordPress/%s', $GLOBALS['wp_version'] );
			}
			if ( isset( $args['headers']['user-agent'] ) ) {
				$args['headers']['user-agent'] = sprintf( 'WordPress/%s', $GLOBALS['wp_version'] );
				log_entry( 'header:user-agent has been seen.' );
			}
			if ( isset( $args['headers']['User-Agent'] ) ) { // Anybody seen this?
				$args['headers']['User-Agent'] = sprintf( 'WordPress/%s', $GLOBALS['wp_version'] );
				log_entry( 'header:User-Agent has been seen.' );
			}
			#	Why remove this? I have not seen it...
			if ( isset( $args['headers']['Referer'] ) ) {
				unset( $args['headers']['Referer'] );
				log_entry( 'headers:Referer has been deleted.' );
			}
		}
		if ( ( $this->options['install'] === 'no' ) ) {
			if ( isset( $args['headers']['wp_install'] ) ) {
				unset( $args['headers']['wp_install'] );
			}
		}
		return $args;
	}

	protected function filter_plugins( $url, $args ) {
		if ( stripos( $url, '://api.wordpress.org/plugins/update-check/' ) !== false ) {
			if ( ! empty( $args['body']['plugins'] ) ) {
				$plugins = json_decode( $args['body']['plugins'] );
				if ( $this->options['plugins'] === 'none' ) {
					$plugins = array();
log_entry('plugins:  none',$plugins);
				} else if ( $this->options['plugins'] === 'active' ) {
					$installed = new stdClass;
					foreach( $plugins->plugins as $plugin => $info ) {
						if ( in_array( $plugin, (array)$plugins->active ) ) {
							$installed->$plugin = $info;
						}
					}
					$plugins->plugins = $installed;
log_entry('plugins:  active',$plugins);
				} else if ( $this->options['plugins'] === 'filter' ) {
					$plugin_filter = $this->options['plugin_list'];
					foreach ( $plugin_filter as $plugin => $status ) {
						if ( $status === 'no' ) {
							if ( isset( $plugins->plugins->$plugin ) ) {
								unset( $plugins->plugins->$plugin );
							}
						}
					}
					$active = new stdClass;
					$count  = 1;
					foreach( $plugins->active as $key => $plugin ) {
						if ( isset( $plugins->plugins->$key ) ) {
							$active->$count = $key;
						}
					}
					$plugins->active = $active;
				}
log_entry('plugins:  done',$plugins);
				$args['body']['plugins'] = json_encode( $plugins );
			}
		}
		return $args;
	}

	protected function filter_themes( $url, $args ) {
		if ( stripos( $url, '://api.wordpress.org/themes/update-check/' ) !== false ) {
			if ( ! empty( $args['body']['themes'] ) ) {
				$themes = json_decode( $args['body']['themes'] );
log_entry($url,$themes);
				if ( $this->options['themes'] === 'none' ) {
					$args['body']['themes'] = json_encode( array() );
					$themes = new stdClass;
log_entry('themes: none',$themes);
				} else if ( $this->options['themes'] === 'active' ) {
					$installed = new stdClass;
					foreach( $themes->themes as $theme => $info ) {
						if ( isset( $themes->active->$theme ) ) {
							$installed->$theme = $info;
						}
					}
					$themes->themes = $installed;
log_entry('themes: active',$themes);
				} else if ( $this->options['themes'] === 'filter' ) {
					$theme_filter  = $this->options['theme_list'];
					$active_backup = $themes->active;
					foreach ( $theme_filter as $theme => $status ) {
						#	Is theme still installed?
						if ( isset( $themes->themes->$theme ) ) {
							#	Is the theme being filtered?
							if ( ( $status === 'no' ) ) {
								unset( $themes->themes->$theme );
								#	Is this the active theme?
								$active_backup = ( $active_backup === $theme ) ? '' : $active_backup;
							} else {
								#	Should a new active theme be assigned?
								$active_backup = ( $active_backup ) ? $active_backup : $theme;
							}
						} else {
							#	Is this the active theme?
							$active_backup = ( $active_backup === $theme ) ? '' : $active_backup;
						}
					}
					$themes->active = $active_backup;
log_entry('themes: filter',$theme_filter,$themes);
				}
log_entry($themes);
				$args['body']['themes'] = json_encode( $themes );
			}
		}
log_entry('themes:  end');
		return $args;
	}

	protected function filter_url( $url ) {
$orig = $url;
		#$keys = array( 'php', 'locale', 'mysql', 'local_package', 'blogs', 'users', 'multisite_enabled', 'initial_db_version',);
		$url_array = parse_url( $url );
		$arg_array = ( isset( $url_array['query'] ) ) ? wp_parse_args( $url_array['query'] ) : array();
log_entry($url_array,$arg_array);
		if ( ! is_multisite() ) {	#	If multisite then these have already been filtered
			if ( isset( $arg_array['blogs'] ) ) {
				$blogs = $this->pre_site_option_blog_count( $arg_array['blogs'], 'fluid_blog_count', '' );
				$url   = add_query_arg( 'blogs', $blogs, $url );
			}
			if ( isset( $arg_array['users'] ) ) {
				$users = $this->pre_site_option_user_count( $arg_array['users'], 'fluid_user_count', '' );
				$url   = add_query_arg( 'users', $users, $url );
			}
		}
		#	I really think that fibbing on this is a bad idea, but the choice is yours
		if ( isset( $arg_array['multisite_enabled'] ) && ( $this->options['blogs'] === 'no' ) ) {
			$arg_array['multisite_enabled'] = 0;
			$url = add_query_arg( 'multisite_enabled', '0', $url );
		}
log_entry(0,$orig,$url);
return $orig;
		return $url;
	}


} # end of class Privacy_My_Way
