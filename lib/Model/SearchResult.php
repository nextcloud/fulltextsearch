<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use JsonSerializable;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;


/**
 * Class SearchResult
 *
 * @package OCA\FullTextSearch\Model
 */
class SearchResult implements ISearchResult, JsonSerializable {

	/** @var IIndexDocument[] */
	private $documents = [];

	/** @var string */
	private $rawResult;

	/** @var IFullTextSearchProvider */
	private $provider;

	/** @var IFullTextSearchPlatform */
	private $platform;

	/** @var int */
	private $total = 0;

	/** @var int */
	private $maxScore = 0;

	/** @var int */
	private $time = 0;

	/** @var boolean */
	private $timedOut = false;

	/** @var ISearchRequest */
	private $request;


	/**
	 * SearchResult constructor.
	 *
	 * @param SearchRequest $searchRequest
	 */
	public function __construct(SearchRequest $searchRequest) {
		$this->request = $searchRequest;
	}


	/**
	 * @param IIndexDocument[] $documents
	 *
	 * @return ISearchResult
	 */
	public function setDocuments(array $documents): ISearchResult {
		$this->documents = $documents;

		return $this;
	}

	/**
	 * @return IIndexDocument[]
	 */
	public function getDocuments(): array {
		return $this->documents;
	}

	/**
	 * @param IIndexDocument $document
	 *
	 * @return ISearchResult
	 */
	public function addDocument(IIndexDocument $document): ISearchResult {
		$this->documents[] = $document;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCount(): int {
		return count($this->documents);
	}


	/**
	 * @param string $result
	 *
	 * @return ISearchResult
	 */
	public function setRawResult(string $result): ISearchResult {
		$this->rawResult = $result;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRawResult(): string {
		return $this->rawResult;
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 *
	 * @return ISearchResult
	 */
	public function setProvider(IFullTextSearchProvider $provider): ISearchResult {
		$this->provider = $provider;

		return $this;
	}

	/**
	 * @return IFullTextSearchProvider
	 */
	public function getProvider(): IFullTextSearchProvider {
		return $this->provider;
	}


	/**
	 * @return IFullTextSearchPlatform
	 */
	public function getPlatform(): IFullTextSearchPlatform {
		return $this->platform;
	}

	/**
	 * @param IFullTextSearchPlatform $platform
	 *
	 * @return ISearchResult
	 */
	public function setPlatform(IFullTextSearchPlatform $platform): ISearchResult {
		$this->platform = $platform;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getTotal(): int {
		return $this->total;
	}

	/**
	 * @param int $total
	 *
	 * @return ISearchResult
	 */
	public function setTotal(int $total): ISearchResult {
		$this->total = $total;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getMaxScore() {
		return $this->maxScore;
	}

	/**
	 * @param int $maxScore
	 *
	 * @return ISearchResult
	 */
	public function setMaxScore(int $maxScore): ISearchResult {
		$this->maxScore = $maxScore;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getTime(): int {
		return $this->time;
	}

	/**
	 * @param int $time
	 *
	 * @return ISearchResult
	 */
	public function setTime(int $time): ISearchResult {
		$this->time = $time;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isTimedOut(): bool {
		return $this->timedOut;
	}

	/**
	 * @param bool $timedOut
	 *
	 * @return ISearchResult
	 */
	public function setTimedOut(bool $timedOut): ISearchResult {
		$this->timedOut = $timedOut;

		return $this;
	}


	/**
	 * @return ISearchRequest
	 */
	public function getRequest(): ISearchRequest {
		return $this->request;
	}

	/**
	 * @param ISearchRequest $request
	 *
	 * @return ISearchResult
	 */
	public function setRequest(ISearchRequest $request): ISearchResult {
		$this->request = $request;

		return $this;
	}


	/**
	 * @param string $category
	 * @param string $value
	 * @param int $count
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function addAggregation(string $category, string $value, int $count): ISearchResult {
		// TODO: Implement addAggregation() method.

		return $this;
	}

	/**
	 * @param string $category
	 *
	 * @return array
	 * @since 15.0.0
	 *
	 */
	public function getAggregations(string $category): array {
		// TODO: Implement getAggregations() method.

		return [];
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {

		$providerObj = $this->getProvider();
		$provider = [];
		if ($providerObj !== null) {
			$provider = [
				'id'   => $providerObj->getId(),
				'name' => $providerObj->getName()
			];
		}

		$platformObj = $this->getPlatform();
		$platform = [];
		if ($platformObj !== null) {
			$platform = [
				'id'   => $platformObj->getId(),
				'name' => $platformObj->getName()
			];
		}

		return [
			'provider'  => $provider,
			'platform'  => $platform,
			'documents' => $this->getDocuments(),
			'info'      => $this->getInfosAll(),
			'meta'      =>
				[
					'timedOut' => $this->isTimedOut(),
					'time'     => $this->getTime(),
					'count'    => $this->getCount(),
					'total'    => $this->getTotal(),
					'maxScore' => $this->getMaxScore()
				]
		];
	}

	public function addInfo(string $k, string $value): ISearchResult {
		return $this;
	}

	public function getInfo(string $k): string {
		return '';
	}

	public function getInfosAll(): array {
		return [];
	}
}

