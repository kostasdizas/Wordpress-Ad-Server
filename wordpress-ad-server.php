<?php
/*
	Plugin Name: Wordpress-Ad-Server
	Description: An advertisment server for wordpress.
	Version: 0.1
	Author: Kostas Dizas
*/

// 
// WP_PLUGIN_DIR, WP_PLUGIN_URL, plugin_basename( __FILE__ )
// 

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
			
			if ( ! check_admin_referer('was-delete_' . $_GET['id']) )
				wp_die( __('Cheating?') );
			
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
	global $current_user;
	
	$current_user = wp_get_current_user();
	
	$ads_class = new WAS_Class();
	
	$view = isset( $_GET['view'] ) ? $_GET['view'] : 'all';
	if ( ! ( $view == 'all' || $view == 'active' || $view == 'inactive' ) )
		$view = 'all';
	
	$vendor = isset($_GET['vendor']) ? $_GET['vendor'] : 'all';
	
	$size = isset($_GET['size']) ? $_GET['size'] : 'all';

	
	
	$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
	
	if ( empty($pagenum) )
		$pagenum = 1;
	
	$per_page = (int) get_user_option( 'was_show_per_page' );
	if ( empty( $per_page ) || $per_page < 1 )
		$per_page = 10;
	
	$entry_count = $ads_class->getEntries( 'state='. $view .'&return=count&entries=all' );
	
	$num_pages = ceil($entry_count / $per_page);
	
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $num_pages,
		'current' => $pagenum
	));
	
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
		<ul class="subsubsub">
			<li>
				<a href="admin.php?page=was-manage"<?php echo ($view=='all')?' class="current"':''; ?>>
					<?php _e('All') ?>
					<span class="count">
						(<?php echo $ads_class->getEntries( 'view=all&return=count' ); ?>)
					</span>
				</a>
			</li> |
			<li>
				<a href="admin.php?page=was-manage&view=active"<?php echo ($view=='active')?' class="current"':''; ?>>
					<?php _e('Active') ?>
					<span class="count">
						(<?php echo $ads_class->getEntries( 'view=active&return=count' ); ?>)
					</span>
				</a>
			</li> |
			<li>
				<a href="admin.php?page=was-manage&view=inactive"<?php echo ($view=='inactive')?' class="current"':''; ?>>
					<?php _e('Inactive') ?>
					<span class="count">
						(<?php echo $ads_class->getEntries( 'view=inactive&return=count' ); ?>)
					</span>
				</a>
			</li>			
		</ul>
		
		<form action="" return="get">
			<input type="hidden" name="page" value="was-manage" />
<?php
if ( function_exists( 'wp_nonce_field' ) )
	wp_nonce_field( 'was-list-form' );
?>
			
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
				<div class="alignleft actions">
					<select name="vendor" id="vendor" class="postform">
						<option<?php selected( $vendor, 'all' ) ?> value="all">Show all vendors</option>
<?php
	foreach( $ads_class->getVendors() as $vvendor ) :
?>
						<option<?php selected( $vendor, $vvendor); ?> value="<?php echo $vvendor ?>"><?php echo $vvendor ?></option>
<?php endforeach; ?>
					</select>
					<select name="size" id="size" class="postform">
						<option<?php selected( $size, 'all' ) ?> value="all">Show all sizes</option>
<?php
	foreach( $ads_class->getSizes() as $ssize ) :
?>
						<option<?php selected( $size, $ssize); ?> value="<?php echo $ssize ?>"><?php echo $ssize ?></option>
<?php endforeach; ?>
					</select>
					<input type="submit" name="post-query-submit" id="post-query-submit" class="button-secondary" value="Filter"  />
				</div>
<?php if ( $page_links ) : ?>
				<div class="tablenav-pages"><?php
	$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
						number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
						number_format_i18n( min( $pagenum * $per_page, $entry_count ) ),
						number_format_i18n( $entry_count ),
						$page_links
						);
	echo $page_links_text;
?></div>
<?php endif; ?>
				<br class="clear" />
			</div>
			
			<br class="clear" />
			
			<table class="widefat post fixed" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
						<th scope="col" class="manage-column column-title"><?php _e('Name'); ?></th>
						<th scope="col" class="manage-column column-vendor"><?php _e('Vendor'); ?></th>
						<th scope="col" class="manage-column column-active"><?php _e('Active'); ?></th>
						<th scope="col" class="manage-column column-size"><?php _e('Size'); ?></th>
						<th scope="col" class="manage-column column-weight"><?php _e('Weight'); ?></th>
					</tr>
				</thead>
				
				<tfoot>
					<tr>
						<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
						<th scope="col" class="manage-column column-title"><?php _e('Name'); ?></th>
						<th scope="col" class="manage-column column-vendor"><?php _e('Vendor'); ?></th>
						<th scope="col" class="manage-column column-active"><?php _e('Active'); ?></th>
						<th scope="col" class="manage-column column-size"><?php _e('Size'); ?></th>
						<th scope="col" class="manage-column column-weight"><?php _e('Weight'); ?></th>
					</tr>
				</tfoot>
				
				<tbody>
<?php
	$args = array(
		'view' => $view,
		'entries' => $per_page,
		'paged' => $pagenum,
		'vendor' => $vendor,
		'size' => $size
	);
	foreach( $ads_class->getEntries( $args ) as $index => $ad ) :
		$act = $ad->isActive();
		$even = ($index&1) ? false : true;
?>
					<tr<?php echo (!$act)?' class="alternate"':'' ?>>
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
<?php
	$del_link = 'admin.php?page=was-manage&amp;action=delete&amp;id='. $ad->id;
	$del_link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($del_link, 'was-delete_' . $ad->id) : $del_link;
?>
									<a class="submitdelete" href="<?php echo $del_link ?>" title="Delete this Entry">
										<?php _e('Delete'); ?>
									</a>
								</span>
							</div>
						</td>
						<td class="column-vendor">
							<?php echo $ad->getVendor(); ?>
						</td>
						<td style="color:<?php echo ( $act ) ? 'green' : 'red'; ?>">
							&#x25CF;
						</td>
						<td class="column-size">
							<?php echo $ad->getSize(); ?>
						</td>
						<td class="column-weight">
							<?php echo $ad->getWeight(); ?>
						</td>
					</tr>
<?php
	endforeach;
?>
				</tbody>
			</table>
			<br class="clear" />
			
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="action2">
						<option value="-1" selected="selected"><?php _e('Bulk Actions'); ?></option>
						<option value="activate"><?php _e('Activate'); ?></option>
						<option value="deactivate"><?php _e('Deactivate'); ?></option>
						<option value="delete"><?php _e('Delete'); ?></option>
					</select>
					<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
				</div>
				<div class="tablenav-pages">
<?php if ( $page_links ) : ?>
					<div class="tablenav-pages"><?php
	$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
						number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
						number_format_i18n( min( $pagenum * $per_page, $entry_count ) ),
						number_format_i18n( $entry_count ),
						$page_links
						);
	echo $page_links_text;
	?></div>
<?php endif; ?>
				</div>
				<br class="clear" />
			</div>
		</form>
		
	</div>
<?php
}


/**
 * Edit/Create entry page
 * 
 * @since 0.1
 * 
 * @param int $id
 */
function was_edit( $id ) {
	$ad = new Advertisment($id);
?>
	<div class="wrap edit-entry">
		<h2><?php _e('Wordpress Ad Server'); ?></h2>
		<h3><?php _e('Edit Entry'); ?></h3>
		<form return="post" action="admin.php?page=was-manage">
<?php
if ( function_exists( 'wp_nonce_field' ) )
	wp_nonce_field( 'was-list-form' );
?>
			<input type="hidden" name="advertisment_id" value="<?php echo $ad->id ?>" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Name</th>
					<td><input type="text" id="advertisment_name" name="advertisment_name" value="<?php echo $ad->getName() ?>" /></td>
				</tr>
				
				<tr valing="top">
					<th scope="row">Vendor</th>
					<td><input type="text" id="advertisment_vendor" name="advertisment_vendor" value="<?php echo $ad->getVendor() ?>" /></td>
				</tr>
				
				<tr valing="top">
					<th scope="row">Code</th>
					<td><textarea id="advertisment_code" name="advertisment_code"><?php echo stripslashes( $ad->getHtml() ) ?></textarea></td>
				</tr>
				
				<tr valing="top">
					<th scope="row">Active</th>
					<td><input type="checkbox" id="advertisment_active" name="advertisment_active" <?php echo ($ad->isActive())?'checked="checked" ':'' ?>/></td>
				</tr>
				
				<tr valing="top">
					<th scope="row">Weight</th>
					<td><input type="text" id="advertisment_weight" name="advertisment_weight" value="<?php echo $ad->getWeight() ?>" /></td>
				</tr>
				
				<tr valing="top">
					<th scope="row">Size</th>
					<td><input type="text" id="advertisment_size" name="advertisment_size" value="<?php echo $ad->getSize() ?>" /></td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
			
			<p>Using Labels (need to research on the effect of &lt;th scope="row"&gt; versus &lt;label&gt; on accessibility, ie. screen readers):</p>
			<label for="advertisment_name">Name</label>
			<input disabled="disabled" type="text" id="advertisment_name" name="advertisment_name" value="<?php echo $ad->getName() ?>" />
			<button disabled="disabled" class="button-primary" type="submit">Update</button>
		</form>
	</div>
<?php
}

/**
 * Plugin Settings Page
 * 
 * @since 0.1
 */
function was_settings() {
?>
	<div class="wrap">
		<h2><?php _e( 'Wordpress Ad Server' ); ?></h2>
		<h3><?php _e( 'Settings' ); ?></h3>
		
		<form return="post" action="options.php">
		<?php settings_fields( 'was-settings-group' ); ?>
			<h4><?php _e( 'Global Settings' ); ?></h4>
			
			<table class="form-table">
				
				<tr valign="top">
					<th scope="row">Some Options</th>
					<td><input type="text" name="was_one" value="<?php echo get_option('was_one'); ?>" /></td>
				</tr>
				
			</table>
			
			<h4><?php _e( 'Personal Settings' ); ?></h4>
			
			<table class="form-table">
			
				<tr valign="top">
					<th scope="row">Advertisments Per Page</th>
					<td><input type="text" name="was_show_per_page" value="<?php echo get_user_option('was_show_per_page'); ?>" /></td>
				</tr>
				
			</table>
			
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		
		</form>

	</div>
<?php
}
function was_register_settings() {
	register_setting( 'was-settings-group', 'was_one' );
	register_setting( 'was-settings-group', 'was_show_per_page', 'was_show_per_page_save' );
}
function was_show_per_page_save( $input ) {
	$user = wp_get_current_user();
	update_user_option( $user->id, 'was_show_per_page', $input );
	$input = get_option( 'was_show_per_page' );
	return $input;
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
	
	add_option( 'was_show_per_page', '10' );
	
	$was_db_version = '0.1';
	
	$table_name = $wpdb->prefix . 'was_data';
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE `". $table_name ."` (
			`advertisment_id` int(11) NOT NULL AUTO_INCREMENT,
			`advertisment_active` int(11) DEFAULT '1' NOT NULL,
			`advertisment_vendor` varchar(200) DEFAULT '' NOT NULL,
			`advertisment_name` varchar(200) DEFAULT '' NOT NULL,
			`advertisment_code` text NOT NULL,
			`advertisment_weight` int(11) DEFAULT '1' NOT NULL,
			`advertisment_size` varchar(20) DEFAULT '125x125' NOT NULL,
			PRIMARY KEY  (`advertisment_id`)
			);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option( 'was_db_version', $was_db_version );
	}
	
	
	$ad = new Advertisment();
	$ad->setName( 'Wordpress Trac' );
	$ad->setHtml( 'http://core.trac.wordpress.org/timeline' );
	$ad->updateDatabase();
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
		add_menu_page( __('Wordpress Ad Servers'), __('Wordpress Ad Server'), 'edit_theme_options', 'was-manage', 'was_manage' );
		
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'was-manage', __('Add New Entry'), __('New Entry'), 'edit_themes', 'was-new', 'was_edit' );

			add_submenu_page( 'was-manage', __('Settings'), __('Settings'), 'edit_themes', 'was-settings', 'was_settings' );
		}
	}
	add_action( 'admin_init', 'was_register_settings' );
}

add_action('admin_menu', 'was_menu');

?>