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


namespace OCA\FullTextSearch\Provider;


use OC\FullTextSearch\Model\IndexDocument;
use OC\FullTextSearch\Model\SearchTemplate;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
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


	/** @var ConfigService */
	private $configService;

	/** @var TestService */
	private $testService;

	/** @var MiscService */
	private $miscService;

	/** @var Runner */
	private $runner;

	/** @var IndexOptions */
	private $indexOptions;


	/**
	 * TestProvider constructor.
	 *
	 * @param ConfigService $configService
	 * @param TestService $testService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ConfigService $configService, TestService $testService, MiscService $miscService
	) {
		$this->configService = $configService;
		$this->testService = $testService;
		$this->miscService = $miscService;
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

