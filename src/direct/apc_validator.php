<?php
// This file validates that a .htaccess file is defined at the root of the project.
// If not, an alert is raised.
require_once dirname(__FILE__)."/../../../../../mouf/Mouf.php";

$jsonObj = array();

if (extension_loaded("apc")) {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "APC extension found";
} else {
	$jsonObj['code'] = "warn";
	$jsonObj['html'] = "APC extension is not installed. The APCCache service will use the configured fallback method instead.";
}

echo json_encode($jsonObj);
exit;

?>