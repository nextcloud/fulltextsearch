<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Provider;

use OC\FullTextSearch\Model\IndexDocument;
use OC\FullTextSearch\Model\SearchTemplate;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\TestService;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\IIndexOptions;
use OCP\FullTextSearch\Model\IRunner;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\FullTextSearch\Model\ISearchTemplate;


/**
 * Class TestProvider
 *
 * @package OCA\FullTextSearch\Provider
 */
class TestProvider implements IFullTextSearchProvider {
	const TEST_PROVIDER_ID = 'test_provider';

	private IRunner $runner;
	private IndexOptions $indexOptions;


	/**
	 * TestProvider constructor.
	 *
	 * @param ConfigService $configService
	 * @param TestService $testService
	 */
	public function __construct(
		private ConfigService $configService,
		private TestService $testService
	) {
	}


	/**
	 * return unique id of the provider
	 */
	public function getId(): string {
		return self::TEST_PROVIDER_ID;
	}


	/**
	 * return name of the provider
	 */
	public function getName(): string {
		return 'Test Provider';
	}


	/**
	 * @return array
	 */
	public function getConfiguration(): array {
		return $this->configService->getConfig();
	}


	public function setRunner(IRunner $runner) {
		$this->runner = $runner;
	}


	/**
	 * @param IIndexOptions $options
	 */
	public function setIndexOptions(IIndexOptions $options) {
		$this->indexOptions = $options;
	}


	/**
	 * @return ISearchTemplate
	 */
	public function getSearchTemplate(): ISearchTemplate {
		return new SearchTemplate();
	}


	/**
	 * called when loading all providers.
	 *
	 * Loading some containers.
	 */
	public function loadProvider() {
	}


	public function generateChunks(string $userId): array {
		return [];
	}


	/**
	 * returns all indexable document for a user.
	 * There is no need to fill the document with content at this point.
	 *
	 * $platform is provided if the mapping needs to be changed.
	 *
	 * @param string $userId
	 * @param string $chunk
	 *
	 * @return IIndexDocument[]
	 */
	public function generateIndexableDocuments(string $userId, string $chunk): array {
		$result = [];

		$result[] = $this->testService->generateIndexDocumentContentLicense($this->indexOptions);
		$result[] = $this->testService->generateIndexDocumentSimple($this->indexOptions);

		return $result;
	}


	/**
	 * generate documents prior to the indexing.
	 *
	 * @param IIndexDocument $document
	 */
	public function fillIndexDocument(IIndexDocument $document) {
	}


	/**
	 * @param IIndexDocument $document
	 *
	 * @return bool
	 */
	public function isDocumentUpToDate(IIndexDocument $document): bool {
		return false;
	}


	/**
	 * @param IIndex $index
	 *
	 * @return IIndexDocument
	 */
	public function updateDocument(IIndex $index): IIndexDocument {
		return new IndexDocument($index->getProviderId(), $index->getDocumentId());
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 */
	public function onInitializingIndex(IFullTextSearchPlatform $platform) {
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 */
	public function onResettingIndex(IFullTextSearchPlatform $platform) {
	}


	/**
	 * not used yet
	 */
	public function unloadProvider() {
	}


	/**
	 * before a search, improve the request
	 *
	 * @param ISearchRequest $request
	 */
	public function improveSearchRequest(ISearchRequest $request) {
	}


	/**
	 * after a search, improve results
	 *
	 * @param ISearchResult $searchResult
	 */
	public function improveSearchResult(ISearchResult $searchResult) {
	}

}

