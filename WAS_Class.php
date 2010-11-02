<?php 
class WAS_Class {

	/**
	 * Variables
	 */
	var $table_name;
	
	/**
	 * Constructor
	 */
	function WAS_Class() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'was_data';
	}
	
	/**
	 * Get all entries from the db table
	 * 
	 * @return array
	 */
	function getEntries() {
		global $wpdb;
		$sql = "SELECT `advertisment_id` FROM `". $this->table_name ."` ORDER BY `advertisment_id` ASC";
		$ads = $wpdb->get_results( $sql );
		
		foreach( $ads as &$ad ) {
			$ad = new Advertisment($ad->advertisment_id, $this->table_name);
		}
		
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
			'advertisment_name' => $wpdb->escape( $entry['advertisment_name'] ),
			'advertisment_code' => $entry['advertisment_code']
		));
	}
}

?>