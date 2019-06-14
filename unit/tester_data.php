<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/security-checks.php');

/*
 * Simulates a REDCap HTTP call via cURL to verify that the
 * data web service works.
 */
header ( 'Content-Type: application/json' );

$json = '{"user":"taylorr4",
"project_id":"1073",
"redcap_url":"http:\/\/10.151.18.250\/redcap_trunk_active\/redcap\/",
"id":"2196438",
"fields":
[{"field":"dob"},
{"field":"gender"},
{"field":"enrollment_date"},
{"field":"glucoseTolerance","timestamp_min":"2013-09-03 10:51:00", "timestamp_max":"2013-09-07 10:51:00"},
{"field":"glucoseTolerance", "timestamp_min":"2013-09-05 00:00:00", "timestamp_max":"2013-09-09 00:00:00"}]}';

// Set POST Variables
$url = 'https://ctscv25.ctsc.med.cornell.edu/ddp/redcap-ddp/index.php';

$ch = curl_init ( $url );
curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt ( $ch, CURLOPT_POSTFIELDS, $json );
curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
		'Content-Type: application/json',
		'Content-Length: ' . strlen ( $json ) 
) );

$result = curl_exec ( $ch );
print $result;

$result = json_decode ( $result );
curl_close ( $ch );

// print_r(json_decode($result));
?>
