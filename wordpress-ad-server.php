<?php
/*
	Plugin Name: Wordpress-Ad-Server
	Description: An advertisment server for wordpress.
	Version: 0.1
	Author: Kostas Dizas
*/

class WAS_Class {

	/**
	 * Variables
	 */
	$table_name;
	
	/**
	 * Constructor
	 */
	function WAS_Class() {
		global $wpdb;
		$this->$table_name = $wpdb->prefix . 'was_data';
	}
	
	/**
	 * Get all entries from the db table
	 * 
	 * @return array
	 */
	function getEntries() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'was_data';
		$sql = "SELECT * FROM ". $table_name ." ORDER BY `advertisment_id` ASC";
		$ads = $wpdb->get_results( $sql );
		return $ads;
	}
	
	/**
	 * Add an entry to the db table
	 * 
	 * @param array $entry
	 */
	function addEntry( $entry ) {
		global $wpdb;
		
		$rows_affected = $wpdb->insert( $this->table_name, array(
			'advertisment_name' => $wpdb->escape( $entry['name'] ),
			'advertisment_code' => $wpdb->escape( $entry['code'] )
		));
	}
}

/**
 * Settings page
 * 
 * @since 0.1
 */
function was_settings() {

	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

?>
	<div class="wrap">
		<h2><?php _e('Wordpress Ad Server'); ?></h2>
		<dl>
<?php
	$ads_class = new WAS_Class();
	foreach( $ads_class->getEntries() as $ad ) {
?>
			<dt><?php echo $ad->advertisment_name ?></dt>
			<dd><textarea><?php echo $ad->advertisment_code ?></textarea></dd>
<?php
	}
?>
		</dl>
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