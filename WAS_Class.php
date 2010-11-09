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
	 * 
	 * @param string $state     (all|active|inactive)
	 * @param string $method    (object|count)
	 * @return array|int
	 */
	function getEntries( $state = 'all', $method = 'object' ) {
		global $wpdb;
		if ( $state == 'active' ) {
			$where = ' WHERE `advertisment_active` = 1';
		} elseif ( $state == 'inactive' ) {
			$where = ' WHERE `advertisment_active` = 0';
		} elseif ( $state == 'all' ) {
			$where = '';
		}
		$sql = "SELECT `advertisment_id` FROM `". $this->table_name ."` ORDER BY `advertisment_id` ASC";
		$ads = $wpdb->get_results( $sql );
		
		if ( $method == 'object' ) {
			foreach( $ads as &$ad ) {
				$ad = new Advertisment( $ad->advertisment_id );
			}
			return $ads;
		} elseif ( $method == 'count' ) {
			return count( $ads );
		} else {
			return false;
		}
		
	}
	
	/**
	 * Get a random entry
	 * 
	 * @param bool $weighted
	 * @return Advertisment
	 */
	function getRandomEntry( $weighted = true ) {
		global $wpdb;
		
		$sql = "SELECT `advertisment_id`, `advertisment_weight`
		FROM `". $this->table_name ."`
		WHERE `advertisment_active` = 1";
		
		$ads = $wpdb->get_results( $sql );
		$sum = 0;
		foreach( $ads as $ad ) {
			$sum += $ad->advertisment_weight;
		}
		$randomInt = rand(1, $sum);
		$sumWeight = 0;
		foreach( $ads as $ad ) {
			$sumWeight += $ad->advertisment_weight;
			if ( $randomInt <= $sumWeight ) {
				$id = $ad->advertisment_id;
				break;
			}
		}
		return new Advertisment( $id );
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
		$ad->setWeight( $entry['advertisment_weight'] );
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
		$ad->setWeight( $entry['advertisment_weight'] );
		$ad->setActive( ( isset( $entry['advertisment_active'] ) ) ? true : false );
		$ad->updateDatabase();
	}
}

?>