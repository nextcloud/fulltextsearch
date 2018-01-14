<?php
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
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\ExtendedIndex;
use OCA\FullTextSearch\Model\Index;

class IndexesRequest extends IndexesRequestBuilder {


	/**
	 * @param Index $index
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function create(Index $index) {

		try {
			$qb = $this->getIndexesInsertSql();
			$qb->setValue('owner_id', $qb->createNamedParameter($index->getOwnerId()))
			   ->setValue('provider_id', $qb->createNamedParameter($index->getProviderId()))
			   ->setValue('document_id', $qb->createNamedParameter($index->getDocumentId()))
			   ->setValue('err', $qb->createNamedParameter($index->getError()))
			   ->setValue('message', $qb->createNamedParameter($index->getMessage()))
			   ->setValue('status', $qb->createNamedParameter($index->getStatus()))
			   ->setValue('indexed', $qb->createNamedParameter($index->getLastIndex()));

			$qb->execute();

			return true;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param Index $index
	 *
	 * @return bool
	 */
	public function update(Index $index) {

		try {
			$this->getIndex($index->getProviderId(), $index->getDocumentId());
		} catch (IndexDoesNotExistException $e) {
			return false;
		}

		$qb = $this->getIndexesUpdateSql();
		$qb->set('status', $qb->createNamedParameter($index->getStatus()));

		if ($index->getOwnerId() !== '') {
			$qb->set('owner_id', $qb->createNamedParameter($index->getOwnerId()));
		}

		if ($index->getLastIndex() > 0) {
			$qb->set('indexed', $qb->createNamedParameter($index->getLastIndex()));
		}

		$qb->set('message', $qb->createNamedParameter($index->getMessage()));

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$qb->execute();

		return true;
	}


	/**
	 * @param Index $index
	 */
	public function deleteIndex(Index $index) {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$qb->execute();
	}


	/**
	 * @param string $providerId
	 */
	public function deleteFromProviderId($providerId) {
		$qb = $this->getIndexesDeleteSql();
		$this->limitToProviderId($qb, $providerId());

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
	 * @param string|int $documentId
	 *
	 * @return ExtendedIndex
	 * @throws IndexDoesNotExistException
	 */
	public function getIndex($providerId, $documentId) {
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
	 * @return Index[]
	 */
	public function getQueuedIndexes() {
		$qb = $this->getIndexesSelectSql();
		$this->limitToQueuedIndexes($qb);

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
	 * @param IFullTextSearchProvider $provider
	 *
	 * @return Index[]
	 */
	public function getIndexesFromProvider(IFullTextSearchProvider $provider) {
		$qb = $this->getIndexesSelectSql();
		$this->limitToProviderId($qb, $provider->getId());

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseIndexesSelectSql($data);
		}
		$cursor->closeCursor();

		return $indexes;
	}


}