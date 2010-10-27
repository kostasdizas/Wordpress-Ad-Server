<?php
/*
	Plugin Name: Wordpress-Ad-Server
	Version: 0.1
	Author: Kostas Dizas
*/

function was_settings() {
?>
	<div class="wrap">
		<h2><?php _e('Wordpress Ad Server'); ?></h2>
		<p>Placeholder</p>
	</div>
<?php
}


/**
 * Initialise the Plugin
 *   ** Create Database
 */
function initialisePlugin() {
	global $wpdb;
	global $table_prefix;
	
	$databaseExists = get_option( 'was_db_exists' );
	
	if ( $databaseExists == "" ) {
		$sql = "CREATE TABLE `". $table_prefix ."was_data` (
			`advertisment_id` int(11) NOT NULL auto_increment,
			`advertisment_active` int(11) NOT NULL default '',
			`advertisment_name` varchar(200) NOT NULL default '',
			`advertisment_code` text NOT NULL,
			PRIMARY KEY  (`advertisment_id`)
			) ENGINE=MyISAM;"
		$wpdb->query( $sql );
		
		// Just temporary solution. Should actually test if database exists..
		add_option( 'was_db_exists', 'yes', 'Flag for the database. If set (to yes) database exists', 'no');
	}
}


add_action('admin_menu', 'was_menu');

function was_menu() {
	if (function_exists('add_menu_page')) {
		add_menu_page( __('Wordpress Ad Server'), __('Wordpress Ad Server'), 'edit_theme_options', 'was-settings', 'was_settings' );
	}
}

?>