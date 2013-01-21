<?php 

use Mouf\MoufUtils;

MoufAdmin::getValidatorService()->registerBasicValidator('APCCache validator', MoufUtils::getUrlPathFromFilePath(__DIR__.'/direct/apc_validator.php', true));
