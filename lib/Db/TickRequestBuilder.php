<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;


use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use OCA\FullTextSearch\Model\Tick;
use OCP\DB\QueryBuilder\IQueryBuilder;


/**
 * Class TickRequestBuilder
 *
 * @package OCA\FullTextSearch\Db
 */
class TickRequestBuilder extends CoreRequestBuilder {


	use TArrayTools;


	/**
	 * Base of the Sql Insert request
	 *
	 * @return IQueryBuilder
	 */
	protected function getTickInsertSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_TICKS);

		return $qb;
	}


	/**
	 * Base of the Sql Update request
	 *
	 * @return IQueryBuilder
	 */
	protected function getTickUpdateSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_TICKS);

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getTickSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			't.id', 't.source', 't.data', 't.first_tick', 't.tick', 't.status', 't.action'
		)
		   ->from(self::TABLE_TICKS, 't');

		$this->defaultSelectAlias = 't';

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getTickDeleteSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_TICKS);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return Tick
	 */
	protected function parseTickSelectSql(array $data): Tick {
		$tick = new Tick($this->get('source', $data, ''), $this->getInt('id', $data, 0));
		$tick->setData($this->getArray('data', $data, []))
			 ->setTick($this->getInt('tick', $data, 0))
			 ->setFirstTick($this->getInt('first_tick', $data, 0))
			 ->setStatus($this->get('status', $data, ''))
			 ->setAction($this->get('action', $data, ''));

		return $tick;
	}

}
