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


use Doctrine\DBAL\Query\QueryBuilder;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IL10N;


/**
 * Class CoreRequestBuilder
 *
 * @package OCA\FullTextSearch\Db
 */
class CoreRequestBuilder {

	const TABLE_INDEXES = 'fulltextsearch_indexes';
	const TABLE_TICKS = 'fulltextsearch_ticks';

	/** @var IDBConnection */
	protected $dbConnection;

	/** @var IL10N */
	protected $l10n;

	/** @var ConfigService */
	protected $configService;

	/** @var MiscService */
	protected $miscService;

	/** @var string */
	protected $defaultSelectAlias;


	/**
	 * CoreRequestBuilder constructor.
	 *
	 * @param IL10N $l10n
	 * @param IDBConnection $connection
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IL10N $l10n, IDBConnection $connection, ConfigService $configService,
		MiscService $miscService
	) {
		$this->l10n = $l10n;
		$this->dbConnection = $connection;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * Limit the request to the Id
	 *
	 * @param IQueryBuilder $qb
	 * @param int $id
	 */
	protected function limitToId(IQueryBuilder &$qb, int $id) {
		$this->limitToDBFieldInt($qb, 'id', $id);
	}


	/**
	 * Limit the request to the OwnerId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $userId
	 */
	protected function limitToOwnerId(IQueryBuilder &$qb, string $userId) {
		$this->limitToDBField($qb, 'owner_id', $userId);
	}


	/**
	 * Limit to the providerId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $providerId
	 */
	protected function limitToProviderId(IQueryBuilder &$qb, string $providerId) {
		$this->limitToDBField($qb, 'provider_id', $providerId);
	}


	/**
	 * Limit to the documentId
	 *
	 * @param IQueryBuilder $qb
	 * @param string $documentId
	 */
	protected function limitToDocumentId(IQueryBuilder &$qb, string $documentId) {
		$this->limitToDBField($qb, 'document_id', $documentId);
	}


	/**
	 * Limit to the entry with at least one Error
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToErr(IQueryBuilder &$qb) {
		$expr = $qb->expr();
		$qb->andWhere($expr->gte('err', $qb->createNamedParameter(1)));
	}


	/**
	 * Limit to the entry with no error
	 *
	 * @param IQueryBuilder $qb
	 */
	protected function limitToNoErr(IQueryBuilder &$qb) {
		$expr = $qb->expr();
		$qb->andWhere($expr->eq('err', $qb->createNamedParameter(0)));
	}


	/**
	 * Limit to documentIds
	 *
	 * @param IQueryBuilder $qb
	 * @param array $documentIds
	 */
	protected function limitToDocumentIds(IQueryBuilder &$qb, array $documentIds) {
		$this->limitToDBFieldArray($qb, 'document_id', $documentIds);
	}


	/**
	 * Limit the request to source
	 *
	 * @param IQueryBuilder $qb
	 * @param string $source
	 */
	protected function limitToSource(IQueryBuilder &$qb, string $source) {
		$this->limitToDBField($qb, 'id', $source);
	}


	/**
	 * Limit the request to status
	 *
	 * @param IQueryBuilder $qb
	 * @param string $status
	 */
	protected function limitToStatus(IQueryBuilder &$qb, string $status) {
		$this->limitToDBField($qb, 'status', $status);
	}


	/**
	 * @param IQueryBuilder $qb
	 * @param string $field
	 * @param string $value
	 */
	private function limitToDBField(IQueryBuilder &$qb, string $field, string $value) {
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
	private function limitToDBFieldInt(IQueryBuilder &$qb, string $field, int $value) {
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
	private function limitToDBFieldArray(IQueryBuilder &$qb, string $field, array $values) {
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
	protected function limitToQueuedIndexes(IQueryBuilder &$qb) {
		$expr = $qb->expr();
		$pf = ($qb->getType() === QueryBuilder::SELECT) ? $this->defaultSelectAlias . '.' : '';
		$qb->andWhere($expr->neq($pf . 'status', $qb->createNamedParameter(Index::INDEX_OK)));
	}

}



