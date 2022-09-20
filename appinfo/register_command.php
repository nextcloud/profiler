<?php

use OCA\Profiler\Command\Enable;

$ocConfig = \OC::$server->getConfig();

$enable = new Enable($ocConfig);
$application->add($enable);

