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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Compare extends Base {
	protected function configure() {
		$this
			->setName('profiler:compare')
			->setDescription('Compare two exported profiles')
			->addArgument('first', InputArgument::REQUIRED, 'First profile to compare')
			->addArgument('second', InputArgument::REQUIRED, 'Second profile to compare');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$from = json_decode(file_get_contents($input->getArgument('first')), true);
		$to = json_decode(file_get_contents($input->getArgument('second')), true);
		$fromQuery = array_sum(array_map(function (array $profile): int {
			return count($profile['collectors']['db']['queries']);
		}, $from));
		$toQuery = array_sum(array_map(function (array $profile): int {
			return count($profile['collectors']['db']['queries']);
		}, $to));

		$result = 0;
		if ($toQuery > $fromQuery) {
			$diff = $toQuery - $fromQuery;
			$output->writeln("<error>$diff queries added</error>\n");
			$result = 1;
		} elseif ($toQuery < $fromQuery) {
			$diff = $fromQuery - $toQuery;
			$output->writeln("<info>$diff queries removed</info>\n");
		}

		$diff = $this->diff($from, $to);
		foreach ($diff as $item) {
			if (isset($item['from']) && isset($item['to'])) {
				$this->compareSingle($output, $item['from'], $item['to']);
			} elseif (isset($item['added'])) {
				$this->outputAdded($output, $item['added']);
			} elseif (isset($item['removed'])) {
				$this->outputRemoved($output, $item['removed']);
			}
		}

		return $result;
	}

	private function diff(array $from, array $to): array {
		$added = [];
		$removed = [];
		$match = [];

		$index = 0;
		while ($from || $to) {
			if ($from && $to && $to[0]['url'] === $from[0]['url']) {
				$match[] = ['from' => array_shift($from), 'to' => array_shift($to), 'index' => $index++];
			} elseif (!$from) {
				$added[] = ['added' => array_shift($to), 'index' => $index++];
			} elseif (!$to) {
				$removed[] = ['removed' => array_shift($from), 'index' => $index++];
			} else {
				$removedCount = 0;
				$addedCount = 0;

				// see if a later `from` matches to `to` in which case items were removed
				foreach ($from as $a) {
					if ($a['url'] === $to[0]['url']) {
						break;
					}
					$removedCount++;
				}

				// see if a later `to` matches to `from` in which case items were added
				foreach ($to as $i => $b) {
					if ($b['url'] === $from[0]['url']) {
						break;
					}
					$addedCount++;
				}

				// if there are both removals and additions, pick the smallest count first
				if ($addedCount === 0) {
					for ($i = 0; $i < $removedCount; $i++) {
						$removed[] = ['removed' => array_shift($from), 'index' => $index++];
					}
				} elseif ($removedCount === 0) {
					for ($i = 0; $i < $addedCount; $i++) {
						$added[] = ['added' => array_shift($to), 'index' => $index++];
					}
				} elseif ($removedCount < $addedCount) {
					for ($i = 0; $i < $removedCount; $i++) {
						$removed[] = ['removed' => array_shift($from), 'index' => $index++];
					}
				} else {
					for ($i = 0; $i < $addedCount; $i++) {
						$added[] = ['added' => array_shift($to), 'index' => $index++];
					}
				}
			}
		}

		// merge matching adds and removes
		foreach ($added as &$addedItem) {
			foreach ($removed as &$removedItem) {
				$addedCount = count($addedItem['added']['collectors']['db']['queries']);
				$removedCount = count($removedItem['removed']['collectors']['db']['queries']);
				if ($addedItem['added']['url'] === $removedItem['removed']['url'] && $addedCount === $removedCount) {
					$match[] = ['from' => $removedItem['removed'], 'to' => $addedItem['added'], 'index' => $addedItem['index']];

					// mark for deletion
					$addedItem['index'] = -1;
					$removedItem['index'] = -1;
				}
			}
		}

		$result = array_merge($added, $removed, $match);
		$result = array_filter($result, function ($item) {
			return $item['index'] >= 0;
		});
		usort($result, function ($a, $b) {
			return $a['index'] <=> $b['index'];
		});

		return $result;
	}

	private function compareSingle(OutputInterface $output, array $from, array $to) {
		$url = $from['url'];
		$fromQueries = array_map(function (array $query) {
			return $query['sql'];
		}, $from['collectors']['db']['queries']);
		$toQueries = array_map(function (array $query) {
			return $query['sql'];
		}, $to['collectors']['db']['queries']);

		if (count($fromQueries) < count($toQueries)) {
			$diff = count($toQueries) - count($fromQueries);
			$output->writeln("<error>≠ $url with $diff queries added</error>");
		} elseif (count($fromQueries) > count($toQueries)) {
			$diff = count($fromQueries) - count($toQueries);
			$output->writeln("<info>≠ $url with $diff queries removed</info>");
		} else {
			$output->writeln("= $url");
		}

		[$added, $removed] = $this->diffQueries($fromQueries, $toQueries);
		foreach ($removed as $query) {
			$output->writeln("<error>  - $query</error>");
		}
		foreach ($added as $query) {
			$output->writeln("<info>  + $query</info>");
		}
	}

	private function diffQueries(array $from, array $to): array {
		// like array_diff, but only removing a single from each for each match

		$added = $to;
		foreach ($from as $query) {
			$index = array_search($query, $added, true);
			if ($index !== false) {
				unset($added[$index]);
			}
		}

		$removed = $from;
		foreach ($to as $query) {
			$index = array_search($query, $removed, true);
			if ($index !== false) {
				unset($removed[$index]);
			}
		}

		return [array_values($added), array_values($removed)];
	}

	private function outputAdded(OutputInterface $output, array $added) {
		$url = $added['url'];
		$queries = count($added['collectors']['db']['queries']);
		$output->writeln("<error>+ $url added with $queries queries</error>");
	}

	private function outputRemoved(OutputInterface $output, array $removed) {
		$url = $removed['url'];
		$queries = count($removed['collectors']['db']['queries']);
		$output->writeln("<info>- $url removed with $queries queries</info>");
	}
}
