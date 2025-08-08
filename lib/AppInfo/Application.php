<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profiler\AppInfo;

use OCA\Profiler\DataCollector\EventLoggerDataProvider;
use OCA\Profiler\DataCollector\HttpDataCollector;
use OCA\Profiler\DataCollector\MemoryDataCollector;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Services\IInitialState;
use OCP\Diagnostics\IEventLogger;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Profiler\IProfiler;
use OCP\Util;

class Application extends App implements IBootstrap {

	/** @var string */
	public const APP_ID = 'profiler';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
		$server = $context->getServerContainer();

		/** @var IProfiler $profiler */
		$profiler = $server->get(IProfiler::class);
		$profiler->add(new HttpDataCollector());
		$profiler->add(new EventLoggerDataProvider($server->get(IEventLogger::class)));
		$profiler->add(new MemoryDataCollector());

		$context->injectFn([$this, 'injectJs']);
	}

	public function injectJs(IProfiler $profiler, IRequest $request, IUserSession $userSession, IGroupManager $groupManager, IInitialState $initialState) {
		if ($profiler->isEnabled() && $userSession->isLoggedIn() && $groupManager->isAdmin($userSession->getUser()->getUID())) {
			$initialState->provideInitialState('request-token', $request->getId());
			Util::addScript('profiler', 'profiler-toolbar');
			Util::addStyle('profiler', 'profiler-toolbar');
		}
	}
}
