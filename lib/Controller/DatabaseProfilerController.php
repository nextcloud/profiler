<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profiler\Controller;

use Exception;
use OC\DB\DbDataCollector;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Profiler\IProfiler;
use PDO;

class DatabaseProfilerController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private IProfiler $profiler,
		private IDBConnection $connection,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Renders the profiler panel for the given token.
	 */
	public function explain(string $token, int $query): Response {
		/** @var DbDataCollector $profile */
		$collector = $this->profiler->loadProfile($token)->getCollector('db');
		$queries = $collector->getQueries();

		if (!isset($queries[$query])) {
			return new NotFoundResponse();
		}

		$query = $queries[$query];
		if (!$query['explainable']) {
			return new NotFoundResponse();
		}

		try {
			if ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE) {
				$results = $this->explainSQLitePlatform($query);
			} elseif ($this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_ORACLE) {
				$results = $this->explainOraclePlatform($query);
			} else {
				$results = $this->explainOtherPlatform($query);
			}
		} catch (Exception $e) {
			return new DataResponse('This query cannot be explained.');
		}

		return new DataResponse([
			'data' => $results,
			'query' => $query,
		]);
	}

	private function explainSQLitePlatform(array $query): array {
		$params = $query['params'];

		return $this->connection->executeQuery('EXPLAIN QUERY PLAN ' . $query['sql'], $params, $query['types'])
			->fetchAll(PDO::FETCH_ASSOC);
	}

	private function explainOtherPlatform(array $query): array {
		$params = $query['params'];

		return $this->connection->executeQuery('EXPLAIN ' . $query['sql'], $params, $query['types'])
			->fetchAll(PDO::FETCH_ASSOC);
	}

	private function explainOraclePlatform(array $query): array {
		$this->connection->executeQuery('EXPLAIN PLAN FOR ' . $query['sql']);

		return $this->connection->executeQuery('SELECT * FROM TABLE(DBMS_XPLAN.DISPLAY())')
			->fetchAll(PDO::FETCH_ASSOC);
	}
}
