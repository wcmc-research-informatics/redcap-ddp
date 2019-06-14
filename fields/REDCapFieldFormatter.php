<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

/**
 * This class takes a result set obtained from an EHR system and returns an 
 * associative field/value pair which holds the response that will be sent to REDCap.
 * In some cases, this response will be the data itself. In other cases, the response
 * will have to be transformed or even generated based on the data itself. This
 * class encapsulates that behavior.
 * 
 * @author     Marcos Davila (mzd2016@med.cornell.edu)
 * @since      v3.10
 * @package    fields
 * @license    Open Source
 * 
 */
class REDCapFieldFormatter {
    
        // Tries to resolve missing class file dependencies at runtime
        function __autoload($className) {	
		if (file_exists ( 'utils/' . $className . '.php' )) {
			require 'utils/' . $className . '.php';
			return true;
		} elseif (file_exists ( 'fields/' . $className . '.php' )) {
			require 'fields/' . $className . '.php';
			return true;
		} elseif (file_exists ( 'dao/' . $className . '.php' )) {
			require 'dao/' . $className . '.php';
			return true;
		} else {
			return false;
		}
	}
	
	public function __construct() {
		$registered = spl_autoload_register(array($this, '__autoload'));
                
                if (!$registered){
                    exit('The autoloader was unable to resolve a missing dependency.');
                }
	}
	
	/**
	 * Given a result set and a configuration item associated with that set,
	 * constructs and returns an associative array which holds the field name
	 * and the value obtained from the database query.
	 */
	public function getField(array $resultSet, array $configItem, $sourcetype) {
		if ($sourcetype === "MSSQL") {
			// Temporal fields have to be processed a different way from one-time fields
			// so let's process these first.
			if ($configItem ['temporal'] == 1) {
				return $this->getTemporalField ( $configItem, $resultSet );
			} elseif ($configItem ['time_format'] === 'Y-m-d') {
				// Dates need to be converted into a REDCap friendly format
				return $this->getDateField ( $configItem, $resultSet );
			} elseif ($configItem ['map'] !== "") {
				return $this->getOptionsField ( $configItem, $resultSet );
			} else {
				return $this->getGeneralField ( $configItem, $resultSet );
			}
		} else {
			exit("getField: DDP does not support connecting to " . $sourcetype . " at this time.");
		}
	}
	
	/*
	 * Formats information that corresponds to one-time datetime values.
	 */
	private function getDateField($configItem, array $resultSet) {
		$date_config = $configItem ["time_format"];
		
		return array (
				"field" => $configItem ['field'],
				"value" => date ( $date_config, strtotime ( $resultSet [$configItem ['Column']] ) ) 
		);
	}
	
	/*
	 * Formats data that corresponds to one-time fields that provide users many options.
	 */
	private function getOptionsField($configItem, array $resultSet) {
		$of_config_key_values = $configItem ["map"];
		
		// Iterate over the keys from the configuration and if the database
		// result matches the name of the key return the array with the
		// associated value from the configuration. This will occur when
		// the user is returning values for checkbox fields.
		
		// Note that we refer to field and not Name. This is because we use Name
		// when we want to refer to the universal name (used by the dict) and 'field'
		// when we want to use the alias of the REDCap field.
		$of_config_keys = array_keys ( $of_config_key_values );
		if (in_array ( $resultSet [$configItem ['Column']], $of_config_keys )) {
			return array (
					"field" => $configItem ['field'],
					"value" => $of_config_key_values [$resultSet [$configItem ['Column']]] 
			);
		} else {
			// Otherwise return the freetext
			return $this->getGeneralField ( $configItem, $resultSet );
		}
	}
	
	/*
	 * Formats data for one-time fields that do not need to be explicitly handled.
	 */
	private function getGeneralField($configItem, array $resultSet) {
		return array (
				"field" => $configItem ['field'],
				"value" => $resultSet [$configItem ['Column']] 
		);
	}
	
	/*
	 * Given a result set and a configuration item associated with that set,
	 * constructs and returns an associative array which holds the field name
	 * and the value obtained from the database query. For temporal fields only.
	 */
	private function getTemporalField($configItem, array $resultSet) {
		if ($configItem ['map'] !== '') {
			return $this->getTemporalOptionsField ( $configItem, $resultSet );
		} else {
			return $this->getTemporalGeneralField ( $configItem, $resultSet );
		}
	}
	
	/*
	 * Formats data that corresponds to temporal fields that provide users many options. This
	 * includes checkboxes, radio buttons, etc. For now, only radio buttons have been tested
	 * and supported.
	 */
	private function getTemporalOptionsField($configItem, array $resultSet) {
		$tof_config_key_values = $configItem ["map"];
		
		// Iterate over the keys from the configuration and if the database
		// result matches the name of the key return the array with the
		// associated value from the configuration. This will occur when
		// the user is returning values for checkbox fields.
		
		// Note that we refer to field and not Name. This is because we use Name
		// when we want to refer to the universal name (used by the dict) and 'field'
		// when we want to use the alias of the REDCap field.
		$tof_config_keys = array_keys ( $tof_config_key_values );
		if (in_array ( $resultSet [$configItem ['Column']], $tof_config_keys )) {
			$date_config = $configItem ["time_format"];
			return array (
					"field" => $configItem ['field'],
					"value" => $tof_config_key_values [$resultSet [$configItem ['Column']]],
					"timestamp" => date ( $date_config, strtotime ( $resultSet [$configItem ['Anchor Date']] ) ) 
			);
		}
	}
	
	/*
	 * Formats data for temporal fields that do not need to be explicitly handled.
	 */
	private function getTemporalGeneralField($configItem, array $resultSet) {
		$date_config = $configItem ["time_format"];
		$db_column = $resultSet [$configItem ['Column']];
		$date = date($date_config, strtotime($db_column) );

		// Determine if date is actually a date. This is done by comparing
        // the value retrieved from the database before and after a conversion
        // to date, and see if they are the same. The date function will fail
        // and default to an invalid date if the data supplied is not a date,
        // so if the two values are different the data in question is not a date.
        // If it's a date return the transformed date. Otherwise just return the data.
	    if ($db_column !== $date) {
			return array (
                                        "field" => $configItem ['field'],
                                        "value" => $db_column,
                                        "timestamp" => date ( $date_config, strtotime ( $resultSet [$configItem ['Anchor Date']] ) )
                        );
                } else {
                        return array (
                                        "field" => $configItem ['field'],
                                        "value" => $date,
                                        "timestamp" => date ( $date_config, strtotime ( $resultSet [$configItem ['Anchor Date']] ) )
                        );

                }
	}
	
}

?>
