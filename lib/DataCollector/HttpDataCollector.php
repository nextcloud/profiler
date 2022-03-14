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

class HttpDataCollector extends AbstractDataCollector {
	public function getName(): string {
		return 'http';
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
		$this->data = [
			'request' => [
				'url' => $request->getRequestUri(),
				'method' => $request->getMethod(),
				'content' => $request->getParams(),
				'httpProtocol' => $request->getHttpProtocol(),
				'userAgent' => $_SERVER['HTTP_USER_AGENT'],
				'params' => $request->getParams(),
			],
			'response' => [
				'headers' => $response->getHeaders(),
				'statusCode' => $response->getStatus(),
				'etag' => $response->getETag(),
			]
		];
	}
}
