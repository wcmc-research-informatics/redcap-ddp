<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

/*
 * Tests correctness of metadata web service.
 */

while ( ! file_exists ( 'utils' ) )
	chdir ( '..' );

include_once 'utils/db_connect.php';
include_once 'utils/constants.php';
include_once 'fields/REDCapFieldFormatter.php';
include_once 'dao/ConfigDAO.php';
include_once 'fieldsFieldDictionary.php';

$project_id = "1073";
$meta = array();
$constants = new Constants();

	// Instantiate new ConfigDAO to hold information from configuration file
	$config = new ConfigDAO ( $constants->pidfiles [$project_id] );
	$configarr = $config->getConfiguration ();
	foreach($configarr as $key => $value){
		foreach($value as $k=>$v){
		$meta[] = $v;
		}	
	}

echo json_encode($meta);
?>
