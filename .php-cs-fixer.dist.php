<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once './vendor-bin/csfixer/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config
	->getFinder()
	->notPath('build')
	->notPath('composer')
	->notPath('l10n')
	->notPath('node_modules')
	->notPath('src')
	->notPath('vendor')
	->in(__DIR__);
return $config;
