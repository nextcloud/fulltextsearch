<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;


use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use OCA\FullTextSearch\Model\Index;
use OCP\DB\QueryBuilder\IQueryBuilder;


/**
 * Class IndexesRequestBuilder
 *
 * @package OCA\FullTextSearch\Db
 */
class IndexesRequestBuilder extends CoreRequestBuilder {


	use TArrayTools;


	/**
	 * Base of the Sql Insert request
	 *
	 * @return IQueryBuilder
	 */
	protected function getIndexesInsertSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_INDEXES);

		return $qb;
	}


	/**
	 * Base of the Sql Update request
	 *
	 * @return IQueryBuilder
	 */
	protected function getIndexesUpdateSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->update(self::TABLE_INDEXES);

		return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	protected function getIndexesSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		/** @noinspection PhpMethodParametersCountMismatchInspection */
		$qb->select(
			'li.owner_id', 'li.provider_id', 'li.document_id', 'li.collection', 'li.source',
			'li.status', 'li.options', 'li.err', 'li.message', 'li.indexed'
		)
		   ->from(self::TABLE_INDEXES, 'li');

		$this->defaultSelectAlias = 'li';

		return $qb;
	}


	/**
	 * Base of the Sql Delete request
	 *
	 * @return IQueryBuilder
	 */
	protected function getIndexesDeleteSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_INDEXES);

		return $qb;
	}


	/**
	 * @param array $data
	 *
	 * @return Index
	 */
	protected function parseIndexesSelectSql(array $data): Index {
		$index =
			new Index($this->get('provider_id', $data, ''), $this->get('document_id', $data, ''));

		$index->setStatus($this->getInt('status', $data))
			  ->setSource($this->get('source', $data, ''))
			  ->setOwnerId($this->get('owner_id', $data, ''))
			  ->setLastIndex($this->getInt('indexed', $data, 0))
			  ->setCollection($this->get('collection', $data));
		$index->setOptions($this->getArray('options', $data, []));
		$index->setErrorCount($this->getInt('err', $data, 0));
		$index->setErrors($this->getArray('message', $data, []));

		return $index;
	}

}

