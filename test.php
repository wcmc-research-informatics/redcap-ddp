Hello.<br>
<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  echo "\nYour connection is NOT encrypted.";
}
else {
  echo "\nYour connection is encrypted!";
}

