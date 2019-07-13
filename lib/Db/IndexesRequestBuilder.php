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


use daita\MySmallPhpTools\Traits\TArrayTools;
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
			'li.owner_id', 'li.provider_id', 'li.document_id', 'li.source', 'li.status',
			'li.options', 'li.err', 'li.message', 'li.indexed'
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
			  ->setLastIndex($this->getInt('indexed', $data, 0));
		$index->setOptions($this->getArray('options', $data, []));
		$index->setErrorCount($this->getInt('err', $data, 0));
		$index->setErrors(json_decode($data['message'], true));

		return $index;
	}

}

