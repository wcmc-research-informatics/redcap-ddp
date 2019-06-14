<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

while ( ! file_exists ( 'utils' ) )
	chdir ( '..' );

include_once 'utils/db_connect.php';
include_once 'utils/constants.php';

/**
 * Checks if user or project has access to DDP
 * 
 * @param unknown $user - user requesting access
 * @param unknown $project_id - project id of REDCap
 * @param unknown $redcap_url - URL of REDCap project
 * @return number - 1 if success, 0 if failure
 * @author     Marcos Davila (mzd2016@med.cornell.edu)
 * @since      v3.10
 * @package    rest
 * @license    Open Source
 */
function auth($user, $project_id, $redcap_url){
    // Reference the project blacklist to make sure the $project_id
    // is valid
    $constants = new Constants();
    	
    $blacklist = array_keys($constants->pidfiles); // Holds contents of file
    $authenticated = 0; // User is not authenticated until otherwise triggered
    
    if (array_search($project_id,$blacklist) === TRUE) {
        $authenticated = 1;
    }
        
    return $authenticated;
    
    
}

?>
