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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Export extends Base {

	public function __construct(
		private IProfiler $profiler,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();
		$this
			->setName('profiler:export')
			->setDescription('Export captured profiles as json')
			->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of profiles to return')
			->addOption('url', null, InputOption::VALUE_REQUIRED, 'Url to list profiles for')
			->addOption('since', null, InputOption::VALUE_REQUIRED, 'Minimum date for listed profiles, as unix timestamp')
			->addOption('before', null, InputOption::VALUE_REQUIRED, 'Maximum date for listed profiles, as unix timestamp');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$since = $input->getOption('since') ? (int)$input->getOption('since') : null;
		$before = $input->getOption('before') ? (int)$input->getOption('before') : null;
		$limit = $input->getOption('limit') ? (int)$input->getOption('limit') : 1000;
		$url = $input->getOption('url');

		$profiles = $this->profiler->find($url, $limit, null, $since, $before);
		$profiles = array_reverse($profiles);
		$profiles = array_map(function (array $profile) {
			return $this->profiler->loadProfile($profile['token']);
		}, $profiles);

		$output->writeln(json_encode($profiles));

		return self::SUCCESS;
	}
}
