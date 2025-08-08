<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2022 Fabien Potencier <fabien@symfony.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later AND MIT
 */

namespace OCA\Profiler\DataCollector;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;

class MemoryDataCollector extends AbstractDataCollector {
	public function getName(): string {
		return 'memory';
	}

	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
		$this->data = [
			'memory' => memory_get_peak_usage(true),
			'memory_limit' => $this->convertToBytes(ini_get('memory_limit')),
		];
	}

	public function getMemory(): int {
		return $this->data['memory'];
	}

	public function getMemoryLimit(): int|float {
		return $this->data['memory_limit'];
	}

	private function convertToBytes(string $memoryLimit): int|float {
		if ($memoryLimit === '-1') {
			return -1;
		}

		$memoryLimit = strtolower($memoryLimit);
		$max = strtolower(ltrim($memoryLimit, '+'));
		if (str_starts_with($max, '0x')) {
			$max = \intval($max, 16);
		} elseif (str_starts_with($max, '0')) {
			$max = \intval($max, 8);
		} else {
			$max = (int)$max;
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
