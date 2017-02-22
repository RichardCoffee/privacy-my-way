<?php

abstract class TCC_Plugin_Plugin {

	use TCC_Trait_Magic;
	use TCC_Trait_ParseArgs;

	protected $admin   = null;
	public    $dbvers  = '0';
	public    $paths;  = null;  #  TCC_Plugin_Paths object
	public    $plugin  = '';
	protected $setting = '';    #  settings link
	protected $state   = '';
	protected $tab     = 'about';
	public    $version = '0.0.0';

	protected function __construct( $args = array() ) {
		$this->parse_args( $args );
		$this->paths = new TCC_Plugin_Paths( $args );
		$this->state = $this->state_check();
	}

	abstract public function initialize();

	public function add_actions() {
	}

	public function add_filters() {
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
	} //*/


	/**  General functions  **/

	abstract public function enqueue_scripts();

	public function state_check() {
		#	silly way of doing this
		defined('TCC_STATE_ALONE')  || define( 'TCC_STATE_ALONE',  'Stand Alone' );     #  Stand Alone
		defined('TCC_STATE_PLUGIN') || define( 'TCC_STATE_PLUGIN', 'Plugin External' ); #  Using Theme Options plugin
		defined('TCC_STATE_THEME')  || define( 'TCC_STATE_THEME',  'Theme Internal' );  #  Using Fluidity internal options
		$state = TCC_STATE_ALONE;
		if ( ! function_exists( 'is_plugin_active' ) ) { include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); }
		if ( is_plugin_active( 'tcc-theme-options/tcc-theme-options.php' ) )       { $state = TCC_STATE_PLUGIN; }
		if ( file_exists( get_template_directory() . '/classes/Form/Admin.php' ) ) { $state = TCC_STATE_THEME; }
		return $state;
	}


  /**  Template functions **/

	#	used in classes/pagetemplater.php
	public function get_stylesheet( $file = 'tcc-plugin.css' ) {
		return $this->paths->get_plugin_file_path( $file );
	}

	/*
	 *  Removes 'Edit' option from plugin page
	 *  Adds 'Settings' option to plugin page
	 *
	 *  sources:  http://code.tutsplus.com/tutorials/integrating-with-wordpress-ui-the-basics--wp-26713
	 */
	public function settings_link( $links, $file ) {
		if ( strpos( $file, $this->plugin ) > -1 ) {
			unset( $links['edit'] );
			if ( is_plugin_active( $file ) ) { // FIXME:  how would this ever get run if the plugin is not active?  why do we need this check?
				$url   = ( $this->settings ) ? $this->setting : admin_url( "admin.php?page=fluidity_options&tab={$this->tab}" );
				$link  = array('settings' => sprintf( '<a href="%1$s"> %2$s </a>', $url, __( 'Settings', 'tcc-plugin' ) ) );
				$links = array_merge( $link, $links );
			}
		}
		return $links;
	}


  /** Update functions **/
/*
  public function check_update() {
    $addr = 'tcc_option_'.$this->tab;
    $data = get_option($addr);
    if (!isset($data['dbvers'])) return;
    if (intval($data['dbvers'],10)>=intval($this->dbvers)) return;
    $this->perform_update($addr);
  }

  private function perform_update($addr) {
    $option = get_option($addr);
    $dbvers = intval($option['dbvers'],10);
    $target = intval($this->dbvers,10);
    while($dbvers<$target) {
      $dbvers++;
      $update_func = "update_$dbvers";
      if ( method_exists( get_called_class(), $update_func ) ) {
        $this->$update_func();
      }
    }
    $option = get_option($addr); // reload in case an update changes an array value
    $option['dbvers']  = $dbvers;
    $option['version'] = $this->paths->version;
    update_option($addr,$option);
  } //*/


}
