<?php
/*
 * Copyright (c) 2012 David Negrier
 *
 * See the file LICENSE.txt for copying permission.
 */

require_once __DIR__."/../../../autoload.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();
if (!$moufManager->instanceExists("apcCacheService")) {
	$apcCacheService = $moufManager->createInstance("Mouf\\Utils\\Cache\\ApcCache");
	$apcCacheService->setName("apcCacheService");
	$apcCacheService->getProperty("defaultTimeToLive")->setValue(3600);
	if ($moufManager->instanceExists("errorLogLogger")) {
		$apcCacheService->getProperty("log")->setValue($moufManager->getInstanceDescriptor("errorLogLogger"));
	}
}

// Let's rewrite the MoufComponents.php file to save the component
$moufManager->rewriteMouf();

// Finally, let's continue the install
InstallUtils::continueInstall();
?>