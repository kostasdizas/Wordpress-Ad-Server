<?php
/*
	Plugin Name: Wordpress-Ad-Server
	Version: 0.1
	Author: Kostas Dizas
*/

function was_settings() {
?>
	<p>PlaceHolder</p>
<?php
}

add_action('admin_menu', 'was_menu');

function was_menu() {
	if (function_exists('add_menu_page')) {
		add_menu_page( __('Wordpress Ad Server'), __('Wordpress Ad Server'), 'edit_theme_options', 'was-settings', 'was_settings' );
	}
}

?>