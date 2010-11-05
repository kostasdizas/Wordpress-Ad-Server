<?php

class WAS_Class {

	/**
	 * Variables
	 */
	var $table_name;
	
	/**
	 * Constructor
	 */
	function __construct() {
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
			$ad = new Advertisment( $ad->advertisment_id );
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
		
		$ad = new Advertisment();
		$ad->setName( $wpdb->escape( $entry['advertisment_name'] ) );
		$ad->setHtml( $entry['advertisment_code'] );
		$ad->setActive( ( isset( $entry['advertisment_active'] ) ) ? true : false );
		$ad->updateDatabase();
	}
	
	/**
	 * Update an existing db entry
	 * 
	 * @param array $entry
	 */
	function editEntry( $entry ) {
		global $wpdb;
		
		$ad = new Advertisment( $entry['advertisment_id'] );
		$ad->setName( $wpdb->escape( $entry['advertisment_name'] ) );
		$ad->setHtml( $entry['advertisment_code'] );
		$ad->setActive( ( isset( $entry['advertisment_active'] ) ) ? true : false );
		$ad->updateDatabase();
	}
}

?>