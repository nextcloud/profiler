<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Robin Appelman <robin@icewind.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Profiler\Command;

use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Compare extends Base {
	protected function configure() {
		$this
			->setName('profiler:compare')
			->setDescription('Compare two exported profiles')
			->addArgument('first', InputArgument::REQUIRED, 'First profile to compare')
			->addArgument('second', InputArgument::REQUIRED, 'Second profile to compare')
			->addOption('backtrace', 'b', InputOption::VALUE_NONE, 'Show backtraces for added queries');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$backtrace = $input->getOption('backtrace');
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
				$this->compareSingle($output, $item['from'], $item['to'], $backtrace);
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

	private function compareSingle(OutputInterface $output, array $from, array $to, bool $backtrace) {
		$url = $from['url'];
		$fromQueries = $from['collectors']['db']['queries'];
		$toQueries = $to['collectors']['db']['queries'];

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
			$output->writeln("<error>  - " . $query['sql'] . "</error>");
		}
		foreach ($added as $query) {
			$output->writeln("<info>  + " . $query['sql'] . "</info>");
			if ($backtrace) {
				$prefixLength = strlen($this->filePrefix($query['backtrace']));
				$backtrace = array_map(function ($trace) use ($prefixLength) {
					return [
						'line' => $trace['file'] ? (substr($trace['file'], $prefixLength) . ' ' . $trace['line']) : '--',
						'call' => $trace['class'] ? ($trace['class'] . $trace['type'] . $trace['function']) : $trace['function'],
					];
				}, $query['backtrace']);
				$callLength = max(array_map(function ($item) {
					return strlen($item['call']);
				}, $backtrace));
				foreach ($backtrace as $trace) {
					$output->writeln("      " . str_pad($trace['call'], $callLength) . ' - ' . $trace['line']);
				}
			}
		}
	}

	public function filePrefix(array $backtrace): string {
		$files = array_map(fn (array $item) => ($item['file'] ?? ''), $backtrace);
		if (count($files) < 2) {
			return $files[0] ?? '';
		}
		$i = 0;
		while (
			isset($files[0][$i]) &&
			array_reduce(
				$files,
				fn ($every, $item) => ($every && $item[$i] == $files[0][$i]),
				true
			)
		) {
			$i++;
		}
		return substr($files[0], 0, $i);
	}

	private function diffQueries(array $from, array $to): array {
		// like array_diff, but only removing a single from each for each match

		$added = $to;
		$addedQueries = array_map(function (array $item) {
			return $item['sql'];
		}, $to);
		foreach ($from as $query) {
			$index = array_search($query['sql'], $addedQueries, true);
			if ($index !== false) {
				unset($added[$index]);
				unset($addedQueries[$index]);
			}
		}

		$removed = $from;
		$removedQueries = array_map(function (array $item) {
			return $item['sql'];
		}, $from);
		foreach ($to as $query) {
			$index = array_search($query['sql'], $removedQueries, true);
			if ($index !== false) {
				unset($removed[$index]);
				unset($removedQueries[$index]);
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
