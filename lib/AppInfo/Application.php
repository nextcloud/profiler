<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Profiler\AppInfo;

use OCP\AppFramework\Services\IInitialState;
use OCP\Diagnostics\IEventLogger;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Profiler\IProfiler;
use OCA\Profiler\DataCollector\EventLoggerDataProvider;
use OCA\Profiler\DataCollector\HttpDataCollector;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
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

		$context->injectFn([$this, 'injectJs']);
	}

	public function injectJs(IProfiler $profiler, IRequest $request, IUserSession $userSession, IGroupManager $groupManager, IInitialState $initialState) {
		if ($profiler->isEnabled() && $userSession->isLoggedIn() && $groupManager->isAdmin($userSession->getUser()->getUID())) {
			$initialState->provideInitialState('request-token', $request->getId());
			Util::addScript('profiler', 'profiler-toolbar');
		}
	}
}
