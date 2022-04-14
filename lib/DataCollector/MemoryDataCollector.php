<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @copyright 2022 Fabien Potencier <fabien@symfony.com>
 *
 * @license AGPL-3.0-or-later AND MIT
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

class MemoryDataCollector extends AbstractDataCollector {
	public function getName(): string {
		return 'memory';
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
		$this->data = [
			'memory' => memory_get_peak_usage(true),
			'memory_limit' => $this->convertToBytes(ini_get('memory_limit')),
		];
	}

	public function getMemory(): int {
		return $this->data['memory'];
	}

	/**
	 * @return int|float
	 */
	public function getMemoryLimit() {
		return $this->data['memory_limit'];
	}

	/**
	 * @return int|float
	 */
	private function convertToBytes(string $memoryLimit) {
		if ('-1' === $memoryLimit) {
			return -1;
		}

		$memoryLimit = strtolower($memoryLimit);
		$max = strtolower(ltrim($memoryLimit, '+'));
		if (str_starts_with($max, '0x')) {
			$max = \intval($max, 16);
		} elseif (str_starts_with($max, '0')) {
			$max = \intval($max, 8);
		} else {
			$max = (int) $max;
		}

		switch (substr($memoryLimit, -1)) {
			case 't': $max *= 1024;
			// no break
			case 'g': $max *= 1024;
			// no break
			case 'm': $max *= 1024;
			// no break
			case 'k': $max *= 1024;
		}
		return $max;
	}
}
