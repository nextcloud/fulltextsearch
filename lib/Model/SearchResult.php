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

use OCA\FullNextSearch\INextSearchProvider;

class SearchResult implements \JsonSerializable {

	/** @var SearchDocument[] */
	private $documents;

	/** @var string */
	private $rawResult;

	/** @var INextSearchProvider */
	private $provider;

	function __construct() {
//		$this->id = $id;
	}


	/**
	 * @param SearchDocument[] $documents
	 *
	 * @return $this
	 */
	public function setDocuments($documents) {
		$this->documents = $documents;

		return $this;
	}

	/**
	 * @return SearchDocument[]
	 */
	public function getDocuments() {
		return $this->documents;
	}

	/**
	 * @param SearchDocument $document
	 *
	 * @return $this
	 */
	public function addDocument(SearchDocument $document) {
		$this->documents[] = $document;

		return $this;
	}


	/**
	 * @param string $result
	 */
	public function setRawResult($result) {
		$this->rawResult = $result;
	}

	/**
	 * @return string
	 */
	public function getRawResult() {
		return $this->rawResult;
	}


	/**
	 * @param INextSearchProvider $provider
	 */
	public function setProvider(INextSearchProvider $provider) {
		$this->provider = $provider;
	}

	/**
	 * @return INextSearchProvider
	 */
	public function getProvider() {
		return $this->provider;
	}


	public function getSize() {
		return count($this->documents);
	}


	/**
	 * TODO: remove rawResult by default
	 */
	public function jsonSerialize() {

		$provider = $this->getProvider();

		return [
			'provider'  => [
				'id'   => $provider->getId(),
				'name' => $provider->getName()
			],
			'raw'       => $this->getRawResult(),
			'documents' => $this->getDocuments(),
			'size'      => $this->getSize()
		];
	}
}