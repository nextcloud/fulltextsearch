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


use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCP\FullTextSearch\Model\IIndex;


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
		$qb = $this->getIndexesInsertSql();
		$qb->setValue('owner_id', $qb->createNamedParameter($index->getOwnerId()))
		   ->setValue('provider_id', $qb->createNamedParameter($index->getProviderId()))
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
			$this->getIndex($index->getProviderId(), $index->getDocumentId());
		} catch (IndexDoesNotExistException $e) {
			return false;
		}

		$qb = $this->getIndexesUpdateSql();
		$qb->set('message', $qb->createNamedParameter(json_encode([])));
		$qb->set('err', $qb->createNamedParameter(0));

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

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
	 *
	 * @return bool
	 */
	public function update(Index $index): bool {

		try {
			$this->getIndex($index->getProviderId(), $index->getDocumentId());
		} catch (IndexDoesNotExistException $e) {
			return false;
		}

		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($index->getStatus()));
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

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$qb->execute();

		return true;
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 * @param int $status
	 */
	public function updateStatus(string $providerId, string $documentId, int $status) {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($status));

		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentId($qb, $documentId);

		$qb->execute();
	}

	/**
	 * @param string $providerId
	 * @param array $indexes
	 * @param int $status
	 */
	public function updateStatuses(string $providerId, array $indexes, int $status) {
		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($status));

		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentIds($qb, $indexes);

		$qb->execute();
	}


	/**
	 * @param IIndex $index
	 */
	public function deleteIndex(IIndex $index) {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$qb->execute();
	}


	/**
	 * @param string $providerId
	 */
	public function deleteFromProviderId(string $providerId) {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToProviderId($qb, $providerId);

		$qb->execute();
	}


	/**
	 *
	 */
	public function reset() {
		$qb = $this->getIndexesDeleteSql();

		$qb->execute();
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
	public function getIndex(string $providerId, string $documentId): Index {
		$qb = $this->getIndexesSelectSql();
		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentId($qb, $documentId);

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
	 * @param array $documentIds
	 *
	 * @return Index[]
	 * @throws IndexDoesNotExistException
	 */
	public function getIndexes(string $providerId, array $documentIds): array {
		$qb = $this->getIndexesSelectSql();
		$this->limitToProviderId($qb, $providerId);
		$this->limitToDocumentIds($qb, $documentIds);

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseIndexesSelectSql($data);
		}
		$cursor->closeCursor();

		if (sizeof($indexes) === 0) {
			throw new IndexDoesNotExistException($this->l10n->t('Index not found'));
		}

		return $indexes;
	}


	/**
	 * @param bool $all
	 *
	 * @return Index[]
	 */
	public function getQueuedIndexes(bool $all = false): array {
		$qb = $this->getIndexesSelectSql();
		$this->limitToQueuedIndexes($qb);
		if ($all === false) {
			$this->limitToNoErr($qb);
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


}
