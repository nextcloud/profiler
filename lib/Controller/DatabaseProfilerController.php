<?php

// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Profiler\Controller;

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Exception;
use OC\DB\DbDataCollector;
use OCP\Profiler\IProfiler;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\IDBConnection;
use OCP\IRequest;
use PDO;

class DatabaseProfilerController extends Controller {
	private IProfiler $profiler;

	private IDBConnection $connection;

	public function __construct(string $appName, IRequest $request, IProfiler $profiler, IDBConnection $connection) {
		parent::__construct($appName, $request);
		$this->profiler = $profiler;
		$this->connection = $connection;
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
			$platform = $this->connection->getDatabasePlatform();
			if ($platform instanceof SqlitePlatform) {
				$results = $this->explainSQLitePlatform($query);
			} elseif ($platform instanceof OraclePlatform) {
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
