<?php

if ( ! function_exists('pmw_remove_dashboard_events_widget' ) ) {
	function pmw_remove_dashboard_events_widget() {
		remove_meta_box( 'dashboard_primary', get_current_screen(), 'side' );
	}
	add_action( 'wp_dashboard_setup',         'pmw_remove_dashboard_events_widget', 100 );
	add_action( 'wp_network_dashboard_setup', 'pmw_remove_dashboard_events_widget', 100 );
	add_action( 'wp_user_dashboard_setup',    'pmw_remove_dashboard_events_widget', 100 );
}
