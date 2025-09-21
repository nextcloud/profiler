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

class HttpDataCollector extends AbstractDataCollector {
	#[\Override]
	public function getName(): string {
		return 'http';
	}

	#[\Override]
	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
		try {
			$content = $request->getParams();
		} catch (\THrowable $ex) {
			$content = null;
		}
		$this->data = [
			'request' => [
				'url' => $request->getRequestUri(),
				'method' => $request->getMethod(),
				'content' => $content,
				'httpProtocol' => $request->getHttpProtocol(),
				'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'params' => $content,
			],
			'response' => [
				'headers' => $response->getHeaders(),
				'statusCode' => $response->getStatus(),
				'etag' => $response->getETag(),
			]
		];
	}
}
