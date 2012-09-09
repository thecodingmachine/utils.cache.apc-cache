<?php
// First, let's request the install utilities
require_once '../../../../../mouf/actions/InstallUtils.php';

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("apcCacheService")) {
	$moufManager->declareComponent("apcCacheService", "ApcCache");
	if ($moufManager->instanceExists("errorLogLogger")) {
		$moufManager->bindComponent("apcCacheService", "log", "errorLogLogger");
	}
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
?>