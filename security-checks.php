<?php

// Check for HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  header('HTTP/1.1 403 Forbidden');
  echo "403\n";
  exit();
}

// Check IP against whitelist.
require($_SERVER['DOCUMENT_ROOT'] . '/ddp/redcap-ddp/utils/ip-whitelist.php');
if (!in_array($_SERVER['REMOTE_ADDR'], $ip_whitelist)) {
  header('HTTP/1.1 403 Forbidden');
  echo "403 ip\n";
  echo $_SERVER['REMOTE_ADDR'];
  exit();
}

?>

