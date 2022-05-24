<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Profiler\Command;


use OC\Core\Command\Base;
use OCP\Profiler\IProfiler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clear extends Base {
	private IProfiler $profiler;

	public function __construct(IProfiler $profiler) {
		parent::__construct();
		$this->profiler = $profiler;
	}

	protected function configure() {
		$this
			->setName('profiler:clear')
			->setDescription('Remove all saved profiles');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->profiler->clear();

		return 0;
	}
}
