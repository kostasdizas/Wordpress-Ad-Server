<?php
/**
 * WAS uninstall procedure.
 */

// Security checks
if( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();
if ( ! is_user_logged_in() )
	wp_die( 'You must be logged in to run this script.' );
if ( ! current_user_can( 'delete_plugins' ) )
	wp_die( __('You do not have sufficient permissions to run this script.') );


/**
 * Drop the WAS database table.
 * 
 * @since 0.1
 */
function was_uninstall_db() {
	global $wpdb;
	
	// Drop the database table
	$table_name = $wpdb->prefix . 'was_data';
	$wpdb->query( 'DROP TABLE IF EXISTS`'. $table_name .'`;' );
}

/**
 * Delete all WAS saved options.
 * 
 * @since 0.1
 */
function was_uninstall_options() {
	global $wpdb;
	
	// Delete all the user options
	$users = $wpdb->get_col( 'SELECT `'. $wpdb->users.ID .'` FROM `'. $wpdb->users .'`' );
	foreach ( $users as $user_id )
		delete_user_option( $user_id, 'was_show_per_page' );
	
	// Delete all the defined options
	delete_option( 'was_db_version' );
}

was_uninstall_db();
was_uninstall_options();

?>