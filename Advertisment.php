<?php

class Advertisment {

	/**
	 * Variables
	 */
	var $id, $table_name, $data;
	
	/**
	 * Constructor
	 * 
	 * @param int    $id
	 * @param string $table_name
	 */
	function Advertisment( $id, $table_name ) {
		global $wpdb;
		
		$this->table_name = $table_name;
		
		if ($id == null) {
			
		} else {
			$this->id = $id;
			$this->data = (array) $wpdb->get_row( $wpdb->prepare(
				"SELECT `advertisment_name`,
				`advertisment_code`, `advertisment_active`
				FROM `". $this->table_name ."`
				WHERE `advertisment_id` = %s", $this->id)
			);
		}
	}
	
	/**
	 * Constructor for new entries
	 */
//	function Advertisment(  ) {
//		
//	}
	
	/**
	 * Returns the html code of the advertisment
	 * 
	 * @return mixed
	 */
	function getHtml() {
		return $this->data['advertisment_code'];
	}

	/**
	 * Returns the name of the advertisment
	 * 
	 * @return mixed
	 */
	function getName() {
		return $this->data['advertisment_name'];
	}
	
	/**
	 * Returns 1 if advertisement is active
	 * 
	 * @return mixed
	 */
	function isActive() {
		return $this->data['advertisment_active'];
	}
	
	/**
	 * Changes the advertisment state to 0 or 1
	 * 
	 * @param mixed $state
	 * @return mixed
	 */
	function setActive( $state ) {
		if ( $state == true ) {
			return ( $this->data['advertisment_active'] = 1 && updateDatabase() );
		} elseif ( $state == false ) {
			return ( $this->data['advertisment_active'] = 0 && updateDatabase() );
		} else {
			return false;
		}
	}
	
	/**
	 * Update the database
	 */
	function updateDatabase() {
		global $wpdb;
		
		if ( isset( $this->id ) ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*)
				FROM `". $this->table_name ."`
				WHERE `advertisment_id` = %s;", $this->id )
			);
		} else {
			$exists = 0;
		}
		
		if ( $exists ) {
			$wpdb->update(
				$this->table_name,
				$this->data,
				array('advertisment_id', $this->id)
			);
		} else {
			$wpdb->insert(
				$this->table_name,
				$this->data
			);
		}
		
		
		return ($exists)?true:false;
	}
}

?>