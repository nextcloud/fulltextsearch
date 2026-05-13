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

	/**
	 * Base of the Sql Insert request
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
	 * Base of the Sql Select request for ticks
	 *
	 * @return IQueryBuilder
	 */
	protected function getTickSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select(
			't.id', 't.source', 't.data', 't.first_tick', 't.tick', 't.status', 't.action'
		)
		   ->from(self::TABLE_TICKS, 't');

		$this->defaultSelectAlias = 't';

		return $qb;
	}

	protected function parseTickSelectSql(array $data): Tick {
		$tick = new Tick($data['t.source'], (int)$data['t.id']);
		$tick->setData(json_decode($data['t.data'], flags: JSON_THROW_ON_ERROR))
			 ->setTick((int)$data['t.tick'])
			 ->setFirstTick((int)$data['t.first_tick'])
			 ->setStatus($data['t.status'])
			 ->setAction($data['t.action']);

		return $tick;
	}
}
