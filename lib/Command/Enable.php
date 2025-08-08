<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profiler\Command;

use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Enable extends Command {
	public function __construct(
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('profiler:enable')
			->setDescription('Enable profiling');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->config->setSystemValue('debug', true);
		$this->config->setSystemValue('profiler', true);
		return self::SUCCESS;
	}
}
