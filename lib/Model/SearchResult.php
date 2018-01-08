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

namespace OCA\FullTextSearch\Model;

use OCA\FullTextSearch\IFullTextSearchProvider;

class SearchResult implements \JsonSerializable {

	/** @var IndexDocument[] */
	private $documents = [];

	/** @var string */
	private $rawResult;

	/** @var IFullTextSearchProvider */
	private $provider;

	/** @var int */
	private $total;

	/** @var int */
	private $maxScore;

	/** @var int */
	private $time;

	/** @var boolean */
	private $timedOut;

	/** @var SearchRequest */
	private $request;


	public function __construct() {
	}


	/**
	 * @param IndexDocument[] $documents
	 *
	 * @return $this
	 */
	public function setDocuments($documents) {
		$this->documents = $documents;

		return $this;
	}

	/**
	 * @return IndexDocument[]
	 */
	public function getDocuments() {
		return $this->documents;
	}

	/**
	 * @param IndexDocument $document
	 *
	 * @return $this
	 */
	public function addDocument(IndexDocument $document) {
		$this->documents[] = $document;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCount() {
		return count($this->documents);
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
	 * @param IFullTextSearchProvider $provider
	 */
	public function setProvider(IFullTextSearchProvider $provider) {
		$this->provider = $provider;
	}

	/**
	 * @return IFullTextSearchProvider
	 */
	public function getProvider() {
		return $this->provider;
	}


	/**
	 * @return int
	 */
	public function getTotal() {
		return $this->total;
	}

	/**
	 * @param int $total
	 */
	public function setTotal($total) {
		$this->total = $total;
	}


	/**
	 * @return int
	 */
	public function getMaxScore() {
		return $this->maxScore;
	}

	/**
	 * @param int $maxScore
	 */
	public function setMaxScore($maxScore) {
		$this->maxScore = $maxScore;
	}


	/**
	 * @return int
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * @param int $time
	 */
	public function setTime($time) {
		$this->time = $time;
	}


	/**
	 * @return bool
	 */
	public function isTimedOut() {
		return $this->timedOut;
	}

	/**
	 * @param bool $timedOut
	 */
	public function setTimedOut($timedOut) {
		$this->timedOut = $timedOut;
	}


	/**
	 * @return SearchRequest
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param SearchRequest $request
	 */
	public function setRequest($request) {
		$this->request = $request;
	}


	/**
	 * @return array<string,array<string,string>|IndexDocument[]|integer>
	 */
	public function jsonSerialize() {

		$provider = $this->getProvider();

		return [
			'provider'  => [
				'id'   => $provider->getId(),
				'name' => $provider->getName()
			],
			'documents' => $this->getDocuments(),
			'meta'      =>
				[
					'timedOut' => $this->isTimedOut(),
					'time'     => $this->getTime(),
					'count'    => $this->getCount(),
					'total'    => $this->getTotal(),
					'maxScore' => $this->getMaxScore(),
					'request'  => $this->getRequest()
				]
		];
	}
}