<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use Exception;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Tick;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;

class TickService {
	public const TABLE_TICKS = 'fulltextsearch_ticks';

	public function __construct(
		protected readonly IL10N $l10n,
		protected readonly IDBConnection $dbConnection,
		protected readonly ConfigService $configService,
	) {
	}

	public function create(Tick $tick): int {
		try {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->insert(self::TABLE_TICKS)
				->setValue('source', $qb->createNamedParameter($tick->getSource()))
				->setValue('data', $qb->createNamedParameter(json_encode($tick->getData())))
				->setValue('action', $qb->createNamedParameter($tick->getAction()))
				->setValue('first_tick', $qb->createNamedParameter($tick->getFirstTick()))
				->setValue('tick', $qb->createNamedParameter($tick->getTick()))
				->setValue('status', $qb->createNamedParameter($tick->getStatus()));

			$qb->executeStatement();

			return $qb->getLastInsertId();
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function update(Tick $tick): bool {
		try {
			$this->getTickById($tick->getId());
		} catch (TickDoesNotExistException $e) {
			return false;
		}

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_TICKS)
			->set('data', $qb->createNamedParameter(json_encode($tick->getData())))
			->set('tick', $qb->createNamedParameter($tick->getTick()))
			->set('action', $qb->createNamedParameter($tick->getAction()))
			->set('status', $qb->createNamedParameter($tick->getStatus()))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($tick->getId())));

		$qb->executeStatement();

		return true;
	}

	/**
	 * Return a tick by its id.
	 *
	 * @throws TickDoesNotExistException
	 */
	public function getTickById(int $id): Tick {
		$qb = $this->getTickSelectSql();
		$qb->andWhere($qb->expr()->eq('t.id', $qb->createNamedParameter($id)));

		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new TickDoesNotExistException($this->l10n->t('Process timed out'));
		}

		return $this->parseTickSelectSql($data);
	}

	/**
	 * Return a list of ticks by their status.
	 *
	 * @return list<Tick>
	 */
	public function getTicksByStatus(string $status): array {
		$ticks = [];

		$qb = $this->getTickSelectSql();
		$qb->andWhere($qb->expr()->eq('t.status', $qb->createNamedParameter($status)));

		$cursor = $qb->executeQuery();

		while ($data = $cursor->fetch()) {
			$ticks[] = $this->parseTickSelectSql($data);
		}
		$cursor->closeCursor();

		return $ticks;
	}

	/**
	 * Base of the Sql Select request for ticks
	 */
	protected function getTickSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select(
			't.id', 't.source', 't.data', 't.first_tick', 't.tick', 't.status', 't.action'
		)
			->from(self::TABLE_TICKS, 't');

		return $qb;
	}

	protected function parseTickSelectSql(array $data): Tick {
		$tick = new Tick($data['source'], (int)$data['id']);
		$tick->setData(json_decode($data['data'], true, JSON_THROW_ON_ERROR))
			->setTick((int)$data['tick'])
			->setFirstTick((int)$data['first_tick'])
			->setStatus($data['status'])
			->setAction($data['action']);

		return $tick;
	}
}
