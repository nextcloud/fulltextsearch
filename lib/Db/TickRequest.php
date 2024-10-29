<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;


use Exception;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Tick;


/**
 * Class TickRequest
 *
 * @package OCA\FullTextSearch\Db
 */
class TickRequest extends TickRequestBuilder {


	/**
	 * @param Tick $tick
	 *
	 * @return int
	 * @throws Exception
	 */
	public function create(Tick $tick): int {

		try {
			$qb = $this->getTickInsertSql();
			$qb->setValue('source', $qb->createNamedParameter($tick->getSource()))
			   ->setValue('data', $qb->createNamedParameter(json_encode($tick->getData())))
			   ->setValue('action', $qb->createNamedParameter($tick->getAction()))
			   ->setValue('first_tick', $qb->createNamedParameter($tick->getFirstTick()))
			   ->setValue('tick', $qb->createNamedParameter($tick->getTick()))
			   ->setValue('status', $qb->createNamedParameter($tick->getStatus()));

			$qb->execute();

			return $qb->getLastInsertId();
		} catch (Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param Tick $tick
	 *
	 * @return bool
	 */
	public function update(Tick $tick): bool {

		try {
			$this->getTickById($tick->getId());
		} catch (TickDoesNotExistException $e) {
			return false;
		}

		$qb = $this->getTickUpdateSql();
		$qb->set('data', $qb->createNamedParameter(json_encode($tick->getData())))
		   ->set('tick', $qb->createNamedParameter($tick->getTick()))
		   ->set('action', $qb->createNamedParameter($tick->getAction()))
		   ->set('status', $qb->createNamedParameter($tick->getStatus()));

		$this->limitToId($qb, $tick->getId());

		$qb->execute();

		return true;
	}


	/**
	 * @param Tick $tick
	 */
	public function deleteIndex(Tick $tick) {
		$qb = $this->getTickDeleteSql();
		$this->limitToId($qb, $tick->getId());
		$this->limitToSource($qb, $tick->getSource());

		$qb->execute();
	}


	/**
	 *
	 */
	public function reset() {
		$qb = $this->getTickDeleteSql();

		$qb->execute();
	}


	/**
	 * return tick.
	 *
	 * @param int $id
	 *
	 * @return Tick
	 * @throws TickDoesNotExistException
	 */
	public function getTickById(int $id): Tick {
		$qb = $this->getTickSelectSql();
		$this->limitToId($qb, $id);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new TickDoesNotExistException($this->l10n->t('Process timed out'));
		}

		return $this->parseTickSelectSql($data);
	}


	/**
	 * return ticks.
	 *
	 * @param string $status
	 *
	 * @return Tick[]
	 */
	public function getTicksByStatus(string $status): array {

		$ticks = [];

		$qb = $this->getTickSelectSql();
		$this->limitToStatus($qb, $status);

		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$ticks[] = $this->parseTickSelectSql($data);
		}
		$cursor->closeCursor();

		return $ticks;
	}


	/**
	 * @param string $source
	 *
	 * @return Tick[]
	 */
	public function getTicksBySource(string $source): array {
		$qb = $this->getTickSelectSql();
		$this->limitToSource($qb, $source);

		$ticks = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$ticks[] = $this->parseTickSelectSql($data);
		}
		$cursor->closeCursor();

		return $ticks;
	}


}
