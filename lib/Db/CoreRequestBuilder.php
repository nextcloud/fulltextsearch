<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Query\QueryBuilder;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Server;
use Psr\Log\LoggerInterface;

class CoreRequestBuilder {
	const TABLE_INDEXES = 'fulltextsearch_index';
	const TABLE_TICKS = 'fulltextsearch_ticks';

	protected string $defaultSelectAlias;
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
			$this->dbConnection->getInner()->close();
		} catch (\Exception) {
		}

		for ($i = 0; $i < 30; $i++) {
			sleep(10);
			try {
				$this->dbConnection->getInner()->connect();
				$this->lastReconnect = time();
				return;
			} catch (\Exception $e) {
				Server::get(LoggerInterface::class)->warning('failed to reconnect', ['exception' => $e, 'i' => $i]);
			}
		}

		throw $ex;
	}

	/**
	 * Limit the request to the Id
	 *
	 * @param IQueryBuilder $qb
	 * @param int $id
	 */
	protected function limitToId(IQueryBuilder $qb, int $id) {
		$this->limitToDBFieldInt($qb, 'id', $id);
	}


	/**
	 * Limit the request to the OwnerId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function limitToOwnerId(IQueryBuilder $qb, string $userId) {
		$this->limitToDBField($qb, 'owner_id', $userId);
	}


	/**
	 * Limit to the providerId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $providerId
	 */
	protected function limitToProviderId(IQueryBuilder $qb, string $providerId) {
		$this->limitToDBField($qb, 'provider_id', $providerId);
	}


	/**
	 * Limit to the documentId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $documentId
	 */
	protected function limitToDocumentId(IQueryBuilder $qb, string $documentId) {
		$this->limitToDBField($qb, 'document_id', $documentId);
	}


	/**
	 * Limit to the documentId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $collection
	 */
	protected function limitToCollection(IQueryBuilder $qb, string $collection) {
		$this->limitToDBField($qb, 'collection', $collection);
	}


	/**
	 * Limit to the entry with at least one Error
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToErr(IQueryBuilder $qb) {
		$expr = $qb->expr();
		$qb->andWhere($expr->gte('err', $qb->createNamedParameter(1)));
	}


	/**
	 * Limit to the entry with no error
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToNoErr(IQueryBuilder $qb) {
		$expr = $qb->expr();
		$qb->andWhere($expr->eq('err', $qb->createNamedParameter(0)));
	}


	/**
	 * Limit to documentIds
	 *
	 * @param IQueryBuilder $qb
	 * @param array $documentIds
	 */
	protected function limitToDocumentIds(IQueryBuilder $qb, array $documentIds) {
		$this->limitToDBFieldArray($qb, 'document_id', $documentIds);
	}


	/**
	 * Limit the request to source
	 *
	 * @param IQueryBuilder $qb
	 * @param string $source
	 */
	protected function limitToSource(IQueryBuilder $qb, string $source) {
		$this->limitToDBField($qb, 'id', $source);
	}


	/**
	 * Limit the request to status
	 *
	 * @param IQueryBuilder $qb
	 * @param string $status
	 */
	protected function limitToStatus(IQueryBuilder $qb, string $status) {
		$this->limitToDBField($qb, 'status', $status);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string $value
	 */
	private function limitToDBField(IQueryBuilder $qb, string $field, string $value) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->defaultSelectAlias . '.' : '';
		$field = $pf . $field;

		$qb->andWhere($expr->eq($field, $qb->createNamedParameter($value)));
	}

	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param int $value
	 */
	private function limitToDBFieldInt(IQueryBuilder $qb, string $field, int $value) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->defaultSelectAlias . '.' : '';
		$field = $pf . $field;

		$qb->andWhere($expr->eq($field, $qb->createNamedParameter($value)));
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string|integer|array $values
	 */
	private function limitToDBFieldArray(IQueryBuilder $qb, string $field, array $values) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->defaultSelectAlias . '.' : '';
		$field = $pf . $field;

		if (!is_array($values)) {
			$values = [$values];
		}

		$orX = $expr->orX();
		foreach ($values as $value) {
			$orX->add($expr->eq($field, $qb->createNamedParameter($value)));
		}

		$qb->andWhere($orX);
	}


	/**
	 * @param IQueryBuilder $qb
	 */
	protected function limitToQueuedIndexes(IQueryBuilder $qb) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->defaultSelectAlias . '.' : '';
		$qb->andWhere($expr->neq($pf . 'status', $qb->createNamedParameter(Index::INDEX_OK)));
	}

}



