<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;


use OCA\FullTextSearch\Exceptions\DatabaseException;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\CollectionService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FullTextSearch\Model\IIndex;
use OCP\IDBConnection;


/**
 * Class IndexesRequest
 *
 * @package OCA\FullTextSearch\Db
 */
class IndexesRequest extends IndexesRequestBuilder {


	/**
	 * @param Index $index
	 *
	 * @return bool
	 */
	public function create(Index $index): bool {
		if (empty($index->getCollection())) {
			$index->setCollection($this->configService->getInternalCollection());
		}

		$qb = $this->getIndexesInsertSql();
		$qb->setValue('owner_id', $qb->createNamedParameter($index->getOwnerId()))
		   ->setValue('provider_id', $qb->createNamedParameter($index->getProviderId()))
		   ->setValue('collection', $qb->createNamedParameter($index->getCollection()))
		   ->setValue('document_id', $qb->createNamedParameter($index->getDocumentId()))
		   ->setValue('source', $qb->createNamedParameter($index->getSource()))
		   ->setValue('err', $qb->createNamedParameter($index->getErrorCount()))
		   ->setValue('message', $qb->createNamedParameter(json_encode($index->getErrors())))
		   ->setValue('status', $qb->createNamedParameter($index->getStatus()))
		   ->setValue('options', $qb->createNamedParameter(json_encode($index->getOptions())))
		   ->setValue('indexed', $qb->createNamedParameter($index->getLastIndex()));

		$qb->execute();

		return true;
	}


	/**
	 * @param Index $index
	 *
	 * @return bool
	 */
	public function resetError(Index $index): bool {
		try {
			$this->getIndex($index->getProviderId(), $index->getDocumentId(), $index->getCollection());
		} catch (IndexDoesNotExistException $e) {
			return false;
		}

		$qb = $this->getIndexesUpdateSql();
		$qb->set('message', $qb->createNamedParameter(json_encode([])));
		$qb->set('err', $qb->createNamedParameter(0));

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());
		$this->limitToCollection($qb, $index->getCollection());
		$qb->execute();

		return true;
	}


	/**
	 *
	 */
	public function resetAllErrors() {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('message', $qb->createNamedParameter(json_encode([])));
		$qb->set('err', $qb->createNamedParameter(0));

		$qb->execute();
	}


	/**
	 * @return Index[]
	 */
	public function getErrorIndexes(): array {
		$qb = $this->getIndexesSelectSql();
		$this->limitToErr($qb);

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseIndexesSelectSql($data);
		}
		$cursor->closeCursor();

		return $indexes;
	}


	/**
	 * @param Index $index
	 */
	public function update(Index $index, bool $statusOnly = false): void {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($index->getStatus()));

		if (!$statusOnly) {
			$qb->set('source', $qb->createNamedParameter($index->getSource()));

			$qb->set('options', $qb->createNamedParameter(json_encode($index->getOptions())));

			if ($index->getOwnerId() !== '') {
				$qb->set('owner_id', $qb->createNamedParameter($index->getOwnerId()));
			}

			if ($index->getLastIndex() > 0) {
				$qb->set('indexed', $qb->createNamedParameter($index->getLastIndex()));
			}

			$qb->set('message', $qb->createNamedParameter(json_encode($index->getErrors())));
			$qb->set('err', $qb->createNamedParameter($index->getErrorCount()));
		}

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());
		$this->limitToCollection($qb, $index->getCollection());

		$qb->executeStatement();
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 * @param int $status
	 */
	public function updateStatus(string $collection, string $providerId, string $documentId, int $status) {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($status));

		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentId($qb, $documentId);
		$this->limitToCollection($qb, $collection);

		$qb->execute();
	}

	/**
	 * @param string $collection
	 * @param string $providerId
	 * @param array $indexes
	 * @param int $status
	 */
	public function updateStatuses(string $collection, string $providerId, array $indexes, int $status) {
		$collection = ($collection === '') ? $this->configService->getInternalCollection() : $collection;

		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($status));

		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentIds($qb, $indexes);
		$this->limitToCollection($qb, $collection);

		$qb->execute();
	}

	/**
	 * @param string $collection
	 * @param string $providerId
	 * @param array $indexes
	 * @param int $status
	 */
	public function resetCollection(string $collection) {
		$collection = ($collection === '') ? $this->configService->getInternalCollection() : $collection;

		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter(IIndex::INDEX_FULL));
		$this->limitToCollection($qb, $collection);

		$qb->execute();
	}

	/**
	 * @param IIndex $index
	 */
	public function deleteIndex(IIndex $index) {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());
		$this->limitToCollection($qb, $index->getCollection());

		$qb->executeStatement();
	}


	/**
	 * @param string $collection
	 */
	public function deleteCollection(string $collection): void {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToCollection($qb, $collection);

		$qb->executeStatement();
	}


	/**
	 * @param string $providerId
	 */
	public function deleteFromProviderId(string $providerId, string $collection = '') {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToProviderId($qb, $providerId);

		$qb->execute();
	}


	/**
	 *
	 */
	public function reset(string $collection = ''): void {
		$qb = $this->getIndexesDeleteSql();
		if ($collection !== '') {
			$this->limitToCollection($qb, $collection);
		}

		$qb->executeStatement();
	}


	/**
	 * return index.
	 *
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return Index
	 * @throws IndexDoesNotExistException
	 */
	public function getIndex(string $providerId, string $documentId, string $collection = ''): Index {
		$collection = ($collection === '') ? $this->configService->getInternalCollection() : $collection;

		$qb = $this->getIndexesSelectSql();
		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentId($qb, $documentId);
		$this->limitToCollection($qb, $collection);

		$cursor = $qb->execute();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data === false) {
			throw new IndexDoesNotExistException($this->l10n->t('Index not found'));
		}

		return $this->parseIndexesSelectSql($data);
	}


	/**
	 * return index.
	 *
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return Index[]
	 */
	public function getIndexes(string $providerId, string $documentId): array {
		$qb = $this->getIndexesSelectSql();
		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentId($qb, $documentId);

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseIndexesSelectSql($data);
		}
		$cursor->closeCursor();

		return $indexes;
	}


	/**
	 * @param bool $all
	 *
	 * @return Index[]
	 */
	public function getQueuedIndexes(string $collection = '', bool $all = false, int $length = 0): array {
		$collection = ($collection === '') ? $this->configService->getInternalCollection() : $collection;

		$qb = $this->getIndexesSelectSql();
		$this->limitToQueuedIndexes($qb);
		if ($all === false) {
			$this->limitToNoErr($qb);
		}

		$this->limitToCollection($qb, $collection);
		if ($length > 0) {
			$qb->setMaxResults($length);
		}

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseIndexesSelectSql($data);
		}
		$cursor->closeCursor();

		return $indexes;
	}


	/**
	 * return list of last indexes from a providerId.
	 *
	 * @param string $providerId
	 *
	 * @return Index[]
	 */
	public function getIndexesFromProvider(string $providerId): array {
		$qb = $this->getIndexesSelectSql();
		$this->limitToProviderId($qb, $providerId);

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$index = $this->parseIndexesSelectSql($data);
			$indexes[$index->getDocumentId()] = $index;
		}
		$cursor->closeCursor();

		return $indexes;
	}


	/**
	 * @return string[]
	 */
	public function getCollections(bool $local = true): array {
		$internal = $this->configService->getInternalCollection();
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select('li.collection')
		   ->from(self::TABLE_INDEXES, 'li');
		$qb->andWhere($qb->expr()->nonEmptyString('li.collection'));
		$qb->andWhere($qb->expr()->neq('li.collection', $qb->createNamedParameter($internal, IQueryBuilder::PARAM_STR)));
		$qb->groupBy('li.collection');

		$collections = [];
		$cursor = $qb->executeQuery();
		while ($data = $cursor->fetch()) {
			$collections[] = $this->get('collection', $data);
		}
		$cursor->closeCursor();

		if (!$local) {
			return $collections;
		}

		return array_merge([$internal], $collections);
	}


	/**
	 * TODO: remove this in NC30+
	 *
	 * @param string $providerId
	 * @param string $documentId
	 */
	public function migrateIndex24(string $providerId, string $documentId): array {
		try {
			$this->configService->requireMigration24();

			return [];
		} catch (DatabaseException $e) {
		}

		// check data from old table.
		/** @var IDBConnection $dbConnection */
		$dbConnection = \OC::$server->get(IDBConnection::class);

		$query = $dbConnection->getQueryBuilder();
		$query->select('*')
			  ->from('fulltextsearch_indexes')
			  ->where($query->expr()->eq('provider_id', $query->createNamedParameter($providerId)))
			  ->andWhere($query->expr()->eq('document_id', $query->createNamedParameter($documentId)));

		$result = $query->executeQuery();
		if ($result->rowCount() === 0) {
			return [];
		}

		$data = $result->fetch();
		$index = $this->parseIndexesSelectSql($data);
		$result->closeCursor();

		$query = $dbConnection->getQueryBuilder();
		$query->delete('fulltextsearch_indexes')
			  ->where($query->expr()->eq('provider_id', $query->createNamedParameter($providerId)))
			  ->andWhere($query->expr()->eq('document_id', $query->createNamedParameter($documentId)));
		$query->executeStatement();

		$this->create($index);

		return $this->getIndexes($providerId, $documentId);
	}
}
