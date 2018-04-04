<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

/**
 * Acts as a container to hold values read in from the configuration file.
 * 
 * @author     Marcos Davila (mzd2016@med.cornell.edu)
 * @since      v0.1
 * @package    dao
 * @license    Open Source
 *
 */
class ConfigDAO {
 
	// An associative array of stdClass objects that hold
	// other objects that hold field->value mappings.	
	private $config = array();
	
	// Reads in configuration file and populate config
	// as an associative array
	public function __construct($filepath){
		$string = file_get_contents($filepath);
		$this->config = json_decode( $string, true );
	}
	
        // Returns configuration
	public function getConfiguration(){
		return $this->config;
	}
}
?>
