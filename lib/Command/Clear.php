<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profiler\Command;

use OC\Core\Command\Base;
use OCP\Profiler\IProfiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clear extends Base {

	public function __construct(
		private IProfiler $profiler,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('profiler:clear')
			->setDescription('Remove all saved profiles');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->profiler->clear();

		return self::SUCCESS;
	}
}
