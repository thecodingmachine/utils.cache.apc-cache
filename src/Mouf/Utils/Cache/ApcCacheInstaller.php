<?php
/*
 * Copyright (c) 2013-2015 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */
namespace Mouf\Utils\Cache;

use Mouf\Installer\PackageInstallerInterface;
use Mouf\MoufManager;

/**
 * An installer class for APC Cache
 */
class ApcCacheInstaller implements PackageInstallerInterface {

    /**
     * (non-PHPdoc)
     * @see \Mouf\Installer\PackageInstallerInterface::install()
     * @param MoufManager $moufManager
     * @throws \Mouf\MoufException
     */
    public static function install(MoufManager $moufManager) {
        if (!$moufManager->instanceExists("apcCacheService")) {
            $apcCacheService = $moufManager->createInstance("Mouf\\Utils\\Cache\\ApcCache");
            $apcCacheService->setName("apcCacheService");
            $apcCacheService->getProperty("defaultTimeToLive")->setValue(3600);

            /*if ($moufManager->instanceExists("errorLogLogger")) {
                $apcCacheService->getProperty("log")->setValue($moufManager->getInstanceDescriptor("errorLogLogger"));
            }*/

            if ($moufManager->instanceExists("fileCacheService")) {
                $apcCacheService->getProperty("fallback")->setValue($moufManager->getInstanceDescriptor("fileCacheService"));
            }
        } else {
            $apcCacheService = $moufManager->getInstanceDescriptor("apcCacheService");
        }

        $configManager = $moufManager->getConfigManager();
        $constants = $configManager->getMergedConstants();
        if (isset($constants['SECRET'])) {
            $apcCacheService->getProperty('prefix')->setValue('SECRET')->setOrigin('config');
        }

        // Let's rewrite the MoufComponents.php file to save the component
        $moufManager->rewriteMouf();

    }
}
