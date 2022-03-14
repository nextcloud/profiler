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

namespace OCA\Profiler\DataCollector;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;
use OCP\Diagnostics\IEventLogger;

class EventLoggerDataProvider extends AbstractDataCollector {
	private IEventLogger $eventLogger;

	public function __construct(IEventLogger $eventLogger) {
		$this->eventLogger = $eventLogger;
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
		$this->data = [];
		foreach ($this->eventLogger->getEvents() as $event) {
			$this->data[$event->getId()] = [
				'start' => $event->getStart(),
				'stop' => $event->getEnd(),
				'description' => $event->getDescription(),
				'duration' => $event->getDuration(),
				'id' => $event->getId(),
			];
		};
	}

	public function getName(): string {
		return 'event';
	}
}
