<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profiler\DataCollector;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;
use OCP\Diagnostics\IEventLogger;

class EventLoggerDataProvider extends AbstractDataCollector {
	public function __construct(
		private IEventLogger $eventLogger,
	) {
	}

	#[\Override]
	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
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

	#[\Override]
	public function getName(): string {
		return 'event';
	}
}
