<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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


namespace OCA\FullTextSearch\Db;


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
	 * @throws \Exception
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
		} catch (\Exception $e) {
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
