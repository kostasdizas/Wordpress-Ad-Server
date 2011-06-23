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
	 * @param string|array $args
	 * @return array|int
	 */
	function getEntries( $args ) {
		global $wpdb;
		
		$default_args = array (
			'view' => 'all',
			'vendor' => 'all',
			'size' => 'all',
			'entries' => 10,
			'paged' => 1,
			'return' => 'object'
		);
		
		$args = wp_parse_args( $args, $default_args );
		
		extract( $args, EXTR_SKIP );
		
		$where = '';
		
		if ( $view != $default_args['view'] ) {
			$where = ( $where == '' ) ? ' WHERE ' : $where . ' AND '; 
			$where .= $wpdb->prepare( '`advertisment_active` = %s', ( $view == 'active' ) ? '1' : '0' );
		}
		
		if ( $vendor != $default_args['vendor'] ) {
			$where = ( $where == '' ) ? ' WHERE ' : $where . ' AND '; 
			$where .= $wpdb->prepare( '`advertisment_vendor` = %s', $vendor );
		}
		
		if ( $size != $default_args['size'] ) {
			$where = ( $where == '' ) ? ' WHERE ' : $where . ' AND '; 
			$where .= $wpdb->prepare( '`advertisment_size` = %s', $size );
		}
		
		$limit = '';
		
		if ( $return != 'count' ) {
			if ( empty( $entries ) || $entries < 1 )
				$entries = 10;
			if ( empty( $paged ) || $paged < 1 )
				$paged = 1;
			$limit = " LIMIT ". ( ( $paged - 1 ) * $entries ) .",". $entries;
		}
		
		$sql = "SELECT `advertisment_id` FROM `". $this->table_name ."`". $where ." ORDER BY `advertisment_id` ASC". $limit;
		
		$ads = $wpdb->get_col( $sql );
		
		if ( $return == 'object' ) {
			foreach( $ads as &$ad ) {
				$ad = new Advertisment( $ad );
			}
			return $ads;
		} elseif ( $return == 'count' ) {
			return $wpdb->num_rows;
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
		$ad->setVendor( $wpdb->escape( $entry['advertisment_vendor'] ) );
		$ad->setHtml( $entry['advertisment_code'] );
		$ad->setWeight( $entry['advertisment_weight'] );
		$ad->setSize( $entry['advertisment_size'] );
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
		$ad->setVendor( $wpdb->escape( $entry['advertisment_vendor'] ) );
		$ad->setHtml( $entry['advertisment_code'] );
		$ad->setWeight( $entry['advertisment_weight'] );
		$ad->setSize( $entry['advertisment_size'] );
		$ad->setActive( ( isset( $entry['advertisment_active'] ) ) ? true : false );
		$ad->updateDatabase();
	}
	
	/**
	 * Delete an entry from the database
	 *
	 * @param int $id
	 */
	function deleteEntry( $id ) {
		$ad = new Advertisment( $id );
		$ad->delete();
	}
	
	/**
	 * Get the distinct advertisment sizes from the database
	 * 
	 * @param string $state
	 * 
	 * @return array
	 */
	function getSizes( $state = 'all' ) {
		global $wpdb;
		
		$sizes = $wpdb->get_col( "SELECT DISTINCT `advertisment_size` FROM `". $this->table_name ."` WHERE `advertisment_size` != '';" );
		
		return $sizes;
	}
	
	/**
	 * Get the distinct vendors from the database
	 * 
	 * @param string $state
	 * 
	 * @return array
	 */
	function getVendors( $state = 'all' ) {
		global $wpdb;
		
		$vendors = $wpdb->get_col( "SELECT DISTINCT `advertisment_vendor` FROM`". $this->table_name ."` WHERE `advertisment_vendor` != '';" );
		
		return $vendors;
	}

}

?>