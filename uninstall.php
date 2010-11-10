<?php

if( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

if ( function_exists( 'register_uninstall_hook' ) )
	register_uninstall_hook( __FILE__, 'was_uninstall_hook' );

function my_uninstall_hook() {
	
	delete_option( 'was_db_version' );
	delete_option( 'was_show_per_page' );

}

?>