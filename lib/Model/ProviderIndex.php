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


namespace OCA\FullNextSearch\Model;

class ProviderIndex {

	/** @var DocumentIndex[] */
	private $indexes;


	public function __construct($indexes) {
		$this->indexes = $indexes;
	}


	/**
	 * @return DocumentIndex[]
	 */
	public function getIndexes() {
		return $this->indexes;
	}


	/**
	 * @param string $documentId
	 *
	 * @return null|DocumentIndex
	 */
	public function getIndex($documentId) {
		foreach ($this->indexes as $index) {
			if ($index->getDocumentId() === (string)$documentId) {
				return $index;
			}
		}

		return null;
	}


	public function documentIsUpToDate(SearchDocument $document) {
		$index = $this->getIndex($document->getId());
		if ($index === null) {
			return false;
		}

		return ($index->getLastIndex() >= $document->getModifiedTime());
	}


}