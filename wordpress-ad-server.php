<?php
/*
	Plugin Name: Wordpress-Ad-Server
	Description: An advertisment server for wordpress.
	Version: 0.1
	Author: Kostas Dizas
*/

// Show notices (DEBUGGING ONLY)
error_reporting(E_ALL);


include_once( 'WAS_Class.php' );
include_once( 'Advertisment.php' );


/**
 * Settings page
 * 
 * @since 0.1
 */
function was_settings() {
	global $wpdb;
	
	$ads_class = new WAS_Class();

	if ( isset( $_POST[ 'advertisment_name' ] ) ) {
		$data = $_POST;
		$ads_class->addEntry($data);
	}
	
	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

?>
	<div class="wrap">
		<h2><?php _e('Wordpress Ad Server'); ?></h2>
		<h3><?php _e('All Database Entries'); ?></h3>
		<dl>
<?php
	foreach( $ads_class->getEntries() as $ad ) {
?>
			<dt>
				<?php echo $ad->getName() ?>
				<span style="font-size:smaller;color:<?php echo ( $ad->isActive() ) ? 'green' : 'red'; ?>">[<?php echo ( $ad->isActive() ) ? 'active' : 'inactive'; ?>]</span>
			</dt>
			<dd><code><?php echo htmlentities($ad->getHtml())  ?></code></dd>
<?php
	}
?>
		</dl>
	</div>
<?php
	was_new();
}


/**
 * New entry form
 * 
 * @todo its a seperate function because it should be displayed in its own page 
 * @todo or modular dialog
 * 
 * @since 0.1
 */
function was_new() {
?>
	<div class="new-entry">
		<h3><?php _e('Add New Entry'); ?></h3>
		<form method="post">
			<label for="advertisment_name">Name</label>
			<input type="text" id="advertisment_name" name="advertisment_name" />
			<br />
			<label for="advertisment_code">Code</label>
			<textarea id="advertisment_code" name="advertisment_code"></textarea>
			<br />
			<label for="advertisment_active">Active</label>
			<input type="checkbox" id="advertisment_active" name="advertisment_active" />
			<br />
			<button type="submit">Save</button>
		</form>
	</div>
<?php	
}


/**
 * Shortcode [was id=""]
 * 
 * @since 0.1
 * 
 * @uses shortcode_atts()
 * @todo does nothing right now. 
 * 
 * @return string
 */
function was_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array(
		'id' => 0
	), $atts ) );
	
	return '<div class="was-' . esc_attr($id) . '">' . $content . '</div>';
}

add_shortcode( 'was', 'was_shortcode' );


/**
 * Install the plugin
 * 
 * This function runs when the plugin is activated. Checks if database table exists and creates it.
 * 
 * @since 0.1
 */
function was_install() {
	global $wpdb;
	$was_db_version = '0.1';
	
	$table_name = $wpdb->prefix . 'was_data';
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE `". $table_name ."` (
			`advertisment_id` int(11) NOT NULL AUTO_INCREMENT,
			`advertisment_active` int(11) DEFAULT '1' NOT NULL,
			`advertisment_name` varchar(200) DEFAULT '' NOT NULL,
			`advertisment_code` text NOT NULL,
			PRIMARY KEY  (`advertisment_id`)
			);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option( 'was_db_version', $was_db_version );
	}
}

register_activation_hook( __FILE__, 'was_install' );


/**
 * Create a new top-level menu page
 * 
 * @uses add_menu_page
 * 
 * @since 0.1
 */
function was_menu() {
	if (function_exists('add_menu_page')) {
		add_menu_page( __('Wordpress Ad Server'), __('Wordpress Ad Server'), 'edit_theme_options', 'was-settings', 'was_settings' );
	}
}

add_action('admin_menu', 'was_menu');

?>