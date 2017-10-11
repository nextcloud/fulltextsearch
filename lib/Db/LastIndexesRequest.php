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


use OCA\FullNextSearch\Model\DocumentIndex;

class LastIndexesRequest extends LastIndexesRequestBuilder {


	/**
	 * @param DocumentIndex $index
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function create(DocumentIndex $index) {

		try {
			$qb = $this->getLastIndexesInsertSql();
			$qb->setValue('owner_id', $qb->createNamedParameter($index->getOwnerId()))
			   ->setValue('provider_id', $qb->createNamedParameter($index->getProviderId()))
			   ->setValue('document_id', $qb->createNamedParameter($index->getDocumentId()))
			   ->setValue('status', $qb->createNamedParameter($index->getStatus()));

			$qb->execute();

			return true;
		} catch (\Exception $e) {
			throw $e;
		}
	}


	/**
	 * @param DocumentIndex $index
	 */
	public function update(DocumentIndex $index) {

		$qb = $this->getLastIndexesUpdateSql();
		$qb->set('owner_id', $qb->createNamedParameter($index->getOwnerId()))
		   ->set('status', $qb->createNamedParameter($index->getStatus()));

		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$qb->execute();
	}


	/**
	 * @param DocumentIndex $index
	 */
	public function delete(DocumentIndex $index) {

		$qb = $this->getLastIndexesDeleteSql();
		$this->limitToProviderId($qb, $index->getProviderId());
		$this->limitToDocumentId($qb, $index->getDocumentId());

		$qb->execute();
	}


	/**
	 * return list of last indexes from a providerId.
	 *
	 * @param string $providerId
	 *
	 * @return DocumentIndex[]
	 */
	public function getLastIndexesFromProviderId($providerId) {
		$qb = $this->getLastIndexesSelectSql();
		$this->limitToProviderId($qb, $providerId);

		$indexes = [];
		$cursor = $qb->execute();
		while ($data = $cursor->fetch()) {
			$indexes[] = $this->parseLastIndexesSelectSql($data);
		}
		$cursor->closeCursor();

		return $indexes;
	}


}