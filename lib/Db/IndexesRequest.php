<?php
/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\FullNextSearch\Db;


use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\Index;

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

		$qb = $this->getIndexesUpdateSql();
		$qb->set('owner_id', $qb->createNamedParameter($index->getOwnerId()))
		   ->set('status', $qb->createNamedParameter($index->getStatus()))
		   ->set('indexed', $qb->createNamedParameter($index->getLastIndex()));

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$result = $qb->execute();

		return ($result > 0);
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
	 * return list of last indexes from a providerId.
	 *
	 * @param INextSearchProvider $provider
	 *
	 * @return Index[]
	 */
	public function getIndexesFromProvider(INextSearchProvider $provider) {
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