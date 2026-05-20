<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Query\QueryBuilder;
use OC\DB\ConnectionAdapter;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Server;
use Psr\Log\LoggerInterface;

class CoreRequestBuilder {
	private int $lastReconnect = 0;

	public function __construct(
		protected IL10N $l10n,
		protected IDBConnection $dbConnection,
		protected ConfigService $configService
	) {
	}

	protected function reconnect(Exception $ex): void {
		if ($this->lastReconnect > time() - 2) {
			// in case we just reconnected a second ago
			throw $ex;
		}

		try {
			/** @var ConnectionAdapter $dbConnection */
			$dbConnection = $this->dbConnection;
			$dbConnection->getInner()->close();
		} catch (\Exception) {
		}

		for ($i = 0; $i < 30; $i++) {
			sleep(10);
			try {
				/** @var ConnectionAdapter $dbConnection */
				$dbConnection = $this->dbConnection;
				$dbConnection->getInner()->connect();
				$this->lastReconnect = time();
				return;
			} catch (\Exception $e) {
				Server::get(LoggerInterface::class)->warning('failed to reconnect', ['exception' => $e, 'i' => $i]);
			}
		}

		throw $ex;
	}
}



