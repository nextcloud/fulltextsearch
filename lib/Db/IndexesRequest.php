<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;


use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FullTextSearch\Model\IIndex;


/**
 * Class IndexesRequest
 *
 * @package OCA\FullTextSearch\Db
 */
class IndexesRequest extends CoreRequestBuilder {
	const TABLE_INDEXES = 'fulltextsearch_index';

	/**
	 * @param Index $index
	 *
	 * @return bool
	 */
	public function create(Index $index): bool {
		if (empty($index->getCollection())) {
			$index->setCollection($this->configService->getInternalCollection());
		}

		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_INDEXES);
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

		try {
			 $qb->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				return $this->create($index);
			}
			throw $e;
		}

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
		$qb->set('message', $qb->createNamedParameter(json_encode([])))
			->set('err', $qb->createNamedParameter(0))
			->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($index->getProviderId())))
			->andWhere($qb->expr()->eq('document_id', $qb->createNamedParameter($index->getDocumentId())))
			->andWhere($qb->expr()->eq('collection', $qb->createNamedParameter($index->getCollection())));
		$qb->executeStatement();

		return true;
	}


	/**
	 *
	 */
	public function resetAllErrors(): void {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('message', $qb->createNamedParameter(json_encode([])));
		$qb->set('err', $qb->createNamedParameter(0));

		$qb->executeStatement();
	}


	/**
	 * @return Index[]
	 */
	public function getErrorIndexes(): array {
		$qb = $this->getIndexesSelectSql();
		$qb->andWhere($qb->expr()->gte('err', $qb->createNamedParameter(1)));

		$indexes = [];
		try {
			$cursor = $qb->executeQuery();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				return $this->getErrorIndexes();
			}
			throw $e;
		}

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

		$qb->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($index->getProviderId())))
			->andWhere($qb->expr()->eq('document_id', $qb->createNamedParameter($index->getDocumentId())))
			->andWhere($qb->expr()->eq('collection', $qb->createNamedParameter($index->getCollection())));

		try {
			$qb->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				$this->update($index, $statusOnly);
				return;
			}
			throw $e;
		}
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 * @param int $status
	 */
	public function updateStatus(string $collection, string $providerId, string $documentId, int $status): void {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($status));

		$qb->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId)))
			->andWhere($qb->expr()->eq('document_id', $qb->createNamedParameter($documentId)))
			->andWhere($qb->expr()->eq('collection', $qb->createNamedParameter($collection)));

		try {
			$qb->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				$this->updateStatus($collection, $providerId, $documentId, $status);
				return;
			}
			throw $e;
		}
	}

	public function resetCollection(string $collection): void {
		$collection = ($collection === '') ? $this->configService->getInternalCollection() : $collection;

		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter(IIndex::INDEX_FULL))
			->where($qb->expr()->eq('document_id', $qb->createNamedParameter($collection)))
			->andWhere($qb->expr()->eq('collection', $qb->createNamedParameter($collection)));

		$qb->executeStatement();
	}

	/**
	 * @param IIndex $index
	 */
	public function deleteIndex(IIndex $index): void {
		$qb = $this->getIndexesDeleteSql();
		$qb->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($index->getProviderId())))
			->andWhere($qb->expr()->eq('document_id', $qb->createNamedParameter($index->getDocumentId())))
			->andWhere($qb->expr()->eq('collection', $qb->createNamedParameter($index->getCollection())));

		try {
			$qb->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				$this->deleteIndex($index);
			}
			throw $e;
		}
	}


	/**
	 * @param string $collection
	 */
	public function deleteCollection(string $collection): void {
		$qb = $this->getIndexesDeleteSql();
		$qb->where($qb->expr()->eq('collection', $qb->createNamedParameter($collection)));

		$qb->executeStatement();
	}

	public function deleteFromProviderId(string $providerId): void {
		$qb = $this->getIndexesDeleteSql();
		$qb->where($qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId)));

		$qb->executeStatement();
	}

	public function reset(string $collection = ''): void {
		$qb = $this->getIndexesDeleteSql();
		if ($collection !== '') {
			$qb->where($qb->expr()->eq('collection', $qb->createNamedParameter($collection)));
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
		$qb->where($qb->expr()->eq('li.provider_id', $qb->createNamedParameter($providerId)))
			->andWhere($qb->expr()->eq('li.document_id', $qb->createNamedParameter($documentId)))
			->andWhere($qb->expr()->eq('li.collection', $qb->createNamedParameter($collection)));

		try {
			$cursor = $qb->executeQuery();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				return $this->getIndex($providerId, $documentId, $collection);
			}
			throw $e;
		}

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
		$qb->where($qb->expr()->eq('li.provider_id', $qb->createNamedParameter($providerId)))
			->andWhere($qb->expr()->eq('li.document_id', $qb->createNamedParameter($documentId)));

		$indexes = [];
		try {
			$cursor = $qb->executeQuery();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				return $this->getIndexes($providerId, $documentId);
			}
			throw $e;
		}

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
		$qb->andWhere($qb->expr()->neq('li.status', $qb->createNamedParameter(Index::INDEX_OK)));
		if ($all === false) {
			$qb->andWhere($qb->expr()->eq('err', $qb->createNamedParameter(0)));
		}
		$qb->andWhere($qb->expr()->eq('li.collection', $qb->createNamedParameter($collection)));

		if ($length > 0) {
			$qb->setMaxResults($length);
		}

		$indexes = [];
		try {
			$cursor = $qb->executeQuery();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				return $this->getQueuedIndexes($collection, $all, $length);
			}
			throw $e;
		}

		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseIndexesSelectSql($data);
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

		try {
			$cursor = $qb->executeQuery();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_CONNECTION_LOST) {
				$this->reconnect($e);
				return $this->getCollections(true);
			}
			throw $e;
		}

		while ($data = $cursor->fetch()) {
			$collections[] = $data['collection'];
		}
		$cursor->closeCursor();

		if (!$local) {
			return $collections;
		}

		return array_merge([$internal], $collections);
	}

	private function getIndexesUpdateSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
        $qb->update(self::TABLE_INDEXES);

        return $qb;
	}

	private function getIndexesDeleteSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_INDEXES);

        return $qb;
	}


	/**
	 * Base of the Sql Select request for Shares
	 *
	 * @return IQueryBuilder
	 */
	private function getIndexesSelectSql(): IQueryBuilder {
		$qb = $this->dbConnection->getQueryBuilder();

		$qb->select(
			'li.owner_id', 'li.provider_id', 'li.document_id', 'li.collection', 'li.source',
			'li.status', 'li.options', 'li.err', 'li.message', 'li.indexed'
		)
		   ->from(self::TABLE_INDEXES, 'li');

		return $qb;
	}

	private function parseIndexesSelectSql(array $data): Index {
		$index = new Index((string)$data['provider_id'], (string)$data['document_id']);

		$index->setStatus((int)$data['status'])
			->setSource($data['source'] ?? '')
			->setOwnerId($data['owner_id'] ?? '')
			->setLastIndex((int)$data['indexed']);
		$index->setCollection($data['collection']);
		$index->setOptions(json_decode($data['options'] ?? [], true, JSON_THROW_ON_ERROR));
		$index->setErrorCount((int)$data['err']);
		$index->setErrors(json_decode($data['message'] ?? [], true, JSON_THROW_ON_ERROR));

		return $index;
	}
}
