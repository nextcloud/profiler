<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Profiler\Command;

use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Command {
	private IConfig $config;

	public function __construct(IConfig $config) {
		parent::__construct();
		$this->config = $config;
	}

	protected function configure() {
		$this
			->setName('profiler:disable')
			->setDescription('Disable profiling');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->config->setSystemValue('profiler', false);
		$output->writeln('<info>Note: debug mode has been left enabled</info>');
		return 0;
	}
}
