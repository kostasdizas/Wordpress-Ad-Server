<?php

class Advertisment {

	/**
	 * Variables
	 */
	var $id, $table_name, $data, $needsUpdate;
	
	/**
	 * Constructor
	 * 
	 * @param int    $id
	 * @param string $table_name
	 */
	function __construct( $id = null ) {
		global $wpdb;
		
		$this->table_name = $wpdb->prefix . 'was_data';
		
		$this->id = $id;
		
		if ($id == null) {
			$this->data = array(
				'advertisment_name' => null,
				'advertisment_code' => null,
				'advertisment_active' => null
			);
		} else {
			$this->data = (array) $wpdb->get_row( $wpdb->prepare(
				"SELECT `advertisment_name`,
				`advertisment_code`, `advertisment_active`
				FROM `". $this->table_name ."`
				WHERE `advertisment_id` = %s", $this->id)
			);
		}
	}
	
	/**
	 * Returns the html code of the advertisment
	 * 
	 * @return mixed
	 */
	function getHtml() {
		if ( ! $this->data['advertisment_code'] ) {
			return 'You haven\'t yet entered any code for this entry.';
		} else {
			return $this->data['advertisment_code'];
		}
	}
	
	/**
	 * Set the html code for the advertisment
	 * 
	 * @param string $html
	 * @return boolean
	 */
	function setHtml($html) {
		$this->needsUpdate[] = 'advertisment_code';
		return $this->data['advertisment_code'] = $html;
	}

	/**
	 * Returns the name of the advertisment
	 * 
	 * @return mixed
	 */
	function getName() {
		if ( ! $this->data['advertisment_name'] ) {
			return 'You haven\'t yet set a name for this entry.';
		} else {
			return $this->data['advertisment_name'];
		}
	}
	
	/**
	 * Set the name for the advertisment
	 * 
	 * @param string $name
	 * @return boolean
	 */
	function setName($name) {
		$this->needsUpdate[] = 'advertisment_name';
		return $this->data['advertisment_name'] = $name;
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
	function setActive( $state = null ) {
		if ( $state == true ) {
			return $this->data['advertisment_active'] = 1;
		} elseif ( $state == false ) {
			return $this->data['advertisment_active'] = 0;
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
		
		
		if ( $exists && $this->needsUpdate ) {
			$wpdb->update(
				$this->table_name,
				$this->data,
				array('advertisment_id' => $this->id)
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