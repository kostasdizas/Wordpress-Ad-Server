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
 * Manage page
 * 
 * @since 0.1
 */
function was_manage() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	$ads_class = new WAS_Class();
	
	if ( isset($_GET['doaction']) || isset($_GET['doaction2']) ) {
		$sendback = remove_query_arg( array('activated', 'deactivated', 'deleted', 'ids'), wp_get_referer() );
		$sendback = add_query_arg( array('page' => 'was-manage'), $sendback );
		
		$doaction = $_GET['action'];
		
		$was_ids = isset($_GET['was-ids']) ? array_map( 'intval', (array) $_GET['was-ids'] ) : explode(',', $_GET['ids']);

		
		switch ( $doaction ) {
			case 'activate':
				$activated = 0;
				foreach ( (array)$was_ids as $was_id) {
					$ad = new Advertisment( $was_id );
					if ( ! $ad->setActive( true, true ) ) {
						wp_die( __('Error in activating...') );
					}
					$activated++;
				}
				$sendback = add_query_arg( array( 'activated' => $activated, 'ids' => join(',', $was_ids) ), $sendback );
				break;
			case 'deactivate':
				$deactivated = 0;
				foreach ( (array)$was_ids as $was_id) {
					$ad = new Advertisment( $was_id );
					if ( ! $ad->setActive( false, true ) ) {
						wp_die( __('Error in deactivating...') );
					}
					$deactivated++;
				}
				$sendback = add_query_arg( array( 'deactivated' => $deactivated, 'ids' => join(',', $was_ids) ), $sendback );
				break;
			case 'delete':
				$deleted = 0;
				foreach ( (array)$was_ids as $was_id) {
					$ad = new Advertisment( $was_id );
					if ( ! $ad->delete() )
						wp_die( __('Error in deleting...') );
					$deleted++;
				}
				$sendback = add_query_arg( array( 'deleted' => $deleted, 'ids' => join(',', $was_ids) ), $sendback );
				break;
		}
		if ( isset($_GET['action']) )
			$sendback = remove_query_arg( array('action', 'action2'), $sendback );
		
?>
		<script type="text/javascript"> window.location='<?php echo $sendback; ?>'; </script>
<?php
		exit();
	}
	
	
	if ( isset( $_POST['advertisment_id'] ) ) { 
		$data = $_POST;
		$ads_class->editEntry($data);
	} elseif ( isset( $_POST[ 'advertisment_name' ] ) ) {
		$data = $_POST;
		$ads_class->addEntry($data);
	}
	
	if ( ! empty( $_GET['action'] ) && isset($_GET['id']) && (int) $_GET['id'] ) {
		if ( $_GET['action'] == 'edit' ) {
			was_edit( $_GET['id'] );
		} elseif ( $_GET['action'] == 'delete' ) {
		
			$sendback = remove_query_arg( array('delete', 'id'), wp_get_referer() );
			$sendback = add_query_arg( array('page' => 'was-manage'), $sendback );
			
			if ( ! $ads_class->deleteEntry( $_GET['id'] ) )
				wp_die( __('Error in deleting...') );
			$sendback = add_query_arg( array(
				'deleted' => 1,
				'ids' => $_GET['id']
			), $sendback );
?>
			<script type="text/javascript"> window.location='<?php echo $sendback; ?>'; </script>
<?php
			exit();
		} elseif ( $_GET['action'] == 'activate' ) {
		
			$sendback = remove_query_arg( array('activate', 'id'), wp_get_referer() );
			$sendback = add_query_arg( array('page' => 'was-manage'), $sendback );
			
			$ad = new Advertisment( $_GET['id'] );
			if ( ! $ad->setActive( true, true ) ) {
				wp_die( __('Error in activating...') );
			}
			$sendback = add_query_arg( array(
				'activated' => 1,
				'ids' => $_GET['id']
			), $sendback );
?>
			<script type="text/javascript"> window.location='<?php echo $sendback; ?>'; </script>
<?php
			exit();
		} elseif ( $_GET['action'] == 'deactivate' ) {
		
			$sendback = remove_query_arg( array('deactivate', 'id'), wp_get_referer() );
			$sendback = add_query_arg( array('page' => 'was-manage'), $sendback );
			
			$ad = new Advertisment( $_GET['id'] );
			if ( ! $ad->setActive( false, true ) ) {
				wp_die( __('Error in deactivating...') );
			}
			$sendback = add_query_arg( array(
				'deactivated' => 1,
				'ids' => $_GET['id']
			), $sendback );
?>
			<script type="text/javascript"> window.location='<?php echo $sendback; ?>'; </script>
<?php
			exit();
		}
	} else {
		was_list();
	}
}

/**
 * Listing page
 * 
 * @since 0.1
 */
function was_list() {
	$view = isset( $_GET['view'] ) ? $_GET['view'] : 'all';
	if ( ! ( $view == 'all' || $view == 'active' || $view == 'inactive' ) )
		$view = 'all';
	$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
	if ( empty($pagenum) )
		$pagenum = 1;
	$per_page = (int) get_user_option( 'was_show_per_page' );
	if ( empty( $per_page ) || $per_page < 1 )
		$per_page = 20;
	
	$ads_class = new WAS_Class();
?>
	<div class="wrap">
		<h2>
			<?php _e('Wordpress Ad Server'); ?>
			<a href="admin.php?page=was-new" class="button add-new-h2"><?php _e('Add New Entry') ?></a>
		</h2>
<?php
	if ( ( isset($_GET['activated'])   && (int) $_GET['activated']   ) ||
		 ( isset($_GET['deactivated']) && (int) $_GET['deactivated'] ) ||
		 ( isset($_GET['deleted'])     && (int) $_GET['deleted']     ) ) {
		
		if ( isset($_GET['activated']) && (int) $_GET['activated'] )
			$action = 'activated';
		
		if ( isset($_GET['deactivated']) && (int) $_GET['deactivated'] )
			$action = 'deactivated';
		
		if ( isset($_GET['deleted']) && (int) $_GET['deleted'] )
			$action = 'deleted';
		
?>
<div id="message" class="updated"><p><?php
		printf( _n( 'Entry %2$s.', '%1$s entries %2$s.', $_GET[$action] ), $_GET[$action], $action );
		unset($_GET[$action]);
?></p></div>
<?php
	}
?>
		<form action="" method="get">
			<ul class="subsubsub">
				<li>
					<a href="admin.php?page=was-manage"<?php echo ($view=='all')?' class="current"':''; ?>>
						<?php _e('All') ?>
						<span class="count">
							(<?php echo $ads_class->getEntries( 'all', 'count' ); ?>)
						</span>
					</a>
				</li> |
				<li>
					<a href="admin.php?page=was-manage&view=active"<?php echo ($view=='active')?' class="current"':''; ?>>
						<?php _e('Active') ?>
						<span class="count">
							(<?php echo $ads_class->getEntries( 'active', 'count' ); ?>)
						</span>
					</a>
				</li> |
				<li>
					<a href="admin.php?page=was-manage&view=inactive"<?php echo ($view=='inactive')?' class="current"':''; ?>>
						<?php _e('Inactive') ?>
						<span class="count">
							(<?php echo $ads_class->getEntries( 'inactive', 'count' ); ?>)
						</span>
					</a>
				</li>			
			</ul>
			
			<input type="hidden" name="page" value="was-manage" />
			
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="action">
						<option value="-1" selected="selected"><?php _e('Bulk Actions'); ?></option>
						<option value="activate"><?php _e('Activate'); ?></option>
						<option value="deactivate"><?php _e('Deactivate'); ?></option>
						<option value="delete"><?php _e('Delete'); ?></option>
					</select>
					<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
				
				</div>
			
				<br class="clear" />
			</div>
			
			<br class="clear" />
			
			<table class="widefat post fixed" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
						<th scope="col" class="manage-column column-title"><?php _e('Name'); ?></th>
						<th scope="col" class="manage-column column-active"><?php _e('Active'); ?></th>
						<th scope="col" class="manage-column column-weight"><?php _e('Weight'); ?></th>
					</tr>
				</thead>
				
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
						<th scope="col" class="manage-column column-title"><?php _e('Name'); ?></th>
						<th scope="col" class="manage-column column-active"><?php _e('Active'); ?></th>
						<th scope="col" class="manage-column column-weight"><?php _e('Weight'); ?></th>
					</tr>
				</tfoot>
				
				<tbody>
<?php
	foreach( $ads_class->getEntries( $view ) as $index => $ad ) {
		$act = $ad->isActive();
		$even = ($index&1) ? false : true;
?>
					<tr<?php echo ($even)?' class="alternate"':'' ?>>
						<th scope="row" class="check-column">
							<input type="checkbox" name="was-ids[]" value="<?php echo $ad->id; ?>" />
						</th>
						<td class="post-title column-title">
							<strong>
								<a class="row-title">
									<?php echo $ad->getName() ?>
								</a>
							</strong>
							<div class="row-actions">
								<span class="<?php echo ( ! $act )?'activate':'deactivate' ?>">
									<a href="admin.php?page=was-manage&action=<?php echo ( ! $act )?'activate':'deactivate' ?>&id=<?php echo $ad->id ?>">
										<?php _e( ucfirst( ( ! $act )?'activate':'deactivate' ) ); ?>
									</a> | 
								</span>
								<span class="edit">
									<a href="admin.php?page=was-manage&action=edit&id=<?php echo $ad->id ?>">
										<?php _e('Edit'); ?>
									</a> | 
								</span>
								<span class="delete">
									<a class="submitdelete" href="admin.php?page=was-manage&action=delete&id=<?php echo $ad->id ?>" title="Delete this Entry">
										<?php _e('Delete'); ?>
									</a> <!-- | --> 
								</span>
					<!--			<span class="edit">
									<a href="" id="post-preview" target="wp-preview" tabindex="4">
										<?php _e('Preview'); ?>
									</a>
								</span>
					-->		</div>
						</td>
						<td style="color:<?php echo ( $act ) ? 'green' : 'red'; ?>">
							&#x25CF;
						</td>
						<td class="column-weight">
							<?php echo $ad->getWeight(); ?>
						</td>
<!-- <code><?php echo htmlentities( $ad->getHtml() )  ?></code> -->
<?php
	}
?>
				</tbody>
			</table>
			
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="action2">
						<option value="-1" selected="selected"><?php _e('Bulk Actions'); ?></option>
						<option value="edit"><?php _e('Edit'); ?></option>
						<option value="delete"><?php _e('Delete'); ?></option>
					</select>
					<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
				
				</div>
			</div>
		</form>
	</div>
<?php
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
	<div class="wrap new-entry">
		<h2><?php _e('Wordpress Ad Server'); ?></h2>
		<h3><?php _e('Add New Entry'); ?></h3>
		<form method="post" action="admin.php?page=was-manage">
			<label for="advertisment_name">Name</label>
			<input type="text" id="advertisment_name" name="advertisment_name" />
			<br />
			<label for="advertisment_code">Code</label>
			<textarea id="advertisment_code" name="advertisment_code"></textarea>
			<br />
			<label for="advertisment_active">Active</label>
			<input type="checkbox" id="advertisment_active" name="advertisment_active" />
			<br />
			<label for="advertisment_weight">Weight</label>
			<input type="text" id="advertisment_weight" name="advertisment_weight" />
			<br />
			<button class="button-primary" type="submit">Create</button>
		</form>
	</div>
<?php	
}


/**
 * Edit entry
 */
function was_edit( $id ) {
	$ad = new Advertisment($id);
?>
	<div class="wrap edit-entry">
		<h2><?php _e('Wordpress Ad Server'); ?></h2>
		<h3><?php _e('Edit Entry'); ?></h3>
		<form method="post" action="admin.php?page=was-manage">
			<input type="hidden" name="advertisment_id" value="<?php echo $ad->id ?>" />
			<label for="advertisment_name">Name</label>
			<input type="text" id="advertisment_name" name="advertisment_name" value="<?php echo $ad->getName() ?>" />
			<br />
			<label for="advertisment_code">Code</label>
			<textarea id="advertisment_code" name="advertisment_code"><?php echo stripslashes( $ad->getHtml() ) ?></textarea>
			<br />
			<label for="advertisment_active">Active</label>
			<input type="checkbox" id="advertisment_active" name="advertisment_active" <?php echo ($ad->isActive())?'checked="checked" ':'' ?>/>
			<br />
			<label for="advertisment_weight">Weight</label>
			<input type="text" id="advertisment_weight" name="advertisment_weight" value="<?php echo $ad->getWeight() ?>" />
			<br />
			<button class="button-primary" type="submit">Update</button>
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
	$ad = new Advertisment( (int)esc_attr( $id ) );
	
	return '<div class="was-' . esc_attr($id) . '">' . $ad->getName() . '</div>';
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
			`advertisment_weight` int(11) DEFAULT '1' NOT NULL,
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
	if ( function_exists( 'add_menu_page' ) ) {
		add_menu_page( __('Wordpress Ad Server'), __('Wordpress Ad Server'), 'edit_theme_options', 'was-manage', 'was_manage' );
		
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'was-manage', __('Add New Entry'), __('New Entry'), 'edit_themes', 'was-new', 'was_new' );
		}
	}
}

add_action('admin_menu', 'was_menu');

?>