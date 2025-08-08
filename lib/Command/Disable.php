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

class Disable extends Command {
	public function __construct(
		private IConfig $config,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();
		$this
			->setName('profiler:disable')
			->setDescription('Disable profiling');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->config->setSystemValue('profiler', false);
		$output->writeln('<info>Note: debug mode has been left enabled</info>');
		return self::SUCCESS;
	}
}
