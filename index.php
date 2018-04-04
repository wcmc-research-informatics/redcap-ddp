<?php

require($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

/*
 * RESTful API which routes REDCap requests from supported projects to the 
 * metadata and data web services.
 * 
 */

// Basic initialization of web service
require ('rest/meta.php');
require ('rest/auth.php');
require ('rest/data.php');

// TODO: Define API settings, such as whether a secure connection is required
function deliver_response($api_response) {
	// Define HTTP responses
	$http_response_code = array (
			200 => 'OK',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found' 
	);
	
	// Set HTTP Response
	header ( 'HTTP/1.1 ' . $api_response ['status'] . ' ' . $http_response_code [$api_response ['status']] );
	
	echo $api_response ['data'];
	
	exit ();
}

// Define whether an HTTPS connection is required
$HTTPS_required = FALSE;

// Define whether user authentication is required
$authentication_required = FALSE;

// Define API response codes and their related HTTP response
$api_response_code = array (
		0 => array (
				'HTTP Response' => 400,
				'Message' => 'Unknown Error' 
		),
		1 => array (
				'HTTP Response' => 200,
				'Message' => 'Success' 
		),
		2 => array (
				'HTTP Response' => 403,
				'Message' => 'HTTPS Required' 
		),
		3 => array (
				'HTTP Response' => 401,
				'Message' => 'Authentication Required' 
		),
		4 => array (
				'HTTP Response' => 401,
				'Message' => 'Authentication Failed' 
		),
		5 => array (
				'HTTP Response' => 404,
				'Message' => 'Invalid Request' 
		),
		6 => array (
				'HTTP Response' => 400,
				'Message' => 'Invalid Response Format' 
		) 
);

// Set default HTTP response of 'ok'
$response ['code'] = 0;
$response ['status'] = 404;
$response ['data'] = NULL;

try {

  if ($authentication_required) {
    $response ['data'] = auth ( $_POST ["user"], $_POST ["project_id"], $_POST ["redcap_url"] );
  }

  // Calls the API
  $response ['code'] = 1;
  $response ['status'] = $api_response_code [$response ['code']] ['HTTP Response'];

  if ($_SERVER ['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
    $metaws = new meta ();
    $response ['data'] = $metaws->process ( $_POST ["user"], $_POST ["project_id"], $_POST ["redcap_url"] );
    unset($metaws);
  } else if ($_SERVER ['CONTENT_TYPE'] == 'application/json') {
    $json = file_get_contents ( 'php://input' );
    $obj = json_decode ( $json, true ); 
    $dataws = new Data ();
    $response ['data'] = $dataws->process ( $obj ["user"], $obj ["project_id"], $obj ["redcap_url"], $obj ["id"], $obj ["fields"] );
    unset($dataws);
  } else {
    throw new Exception('Missing or unknown content type.');
  }

  // Return Response to browser
  deliver_response ( $response );

} catch (Exception $ex) {
  exit($ex->getMessage());
}

?>

