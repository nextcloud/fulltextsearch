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


namespace OCA\FullTextSearch\Provider;

use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\IFullTextSearchPlatform;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Model\SearchResult;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\TestService;

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

		$this->indexOptions = new IndexOptions();
	}


	/**
	 * return unique id of the provider
	 */
	public function getId() {
		return self::TEST_PROVIDER_ID;
	}


	/**
	 * return name of the provider
	 */
	public function getName() {
		return 'Test Provider';
	}


	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->configService->getAppValue('installed_version');
	}


	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configService->getConfig();
	}


	/**
	 * @return string
	 */
	public function getAppId() {
		return Application::APP_NAME;
	}


	public function setRunner(Runner $runner) {
		$this->runner = $runner;
	}


	/**
	 * @param IndexOptions $options
	 */
	public function setIndexOptions($options) {
		$this->indexOptions = $options;
	}


	/**
	 * @return array
	 */
	public function getOptionsTemplate() {
		return [];
	}


	/**
	 * called when loading all providers.
	 *
	 * Loading some containers.
	 */
	public function loadProvider() {
	}


	/**
	 * returns all indexable document for a user.
	 * There is no need to fill the document with content at this point.
	 *
	 * $platform is provided if the mapping needs to be changed.
	 *
	 * @param string $userId
	 *
	 * @return IndexDocument[]
	 */
	public function generateIndexableDocuments($userId) {
		$result = [];

		$result[] = $this->testService->generateIndexDocumentContentLicense($this->indexOptions);
		$result[] = $this->testService->generateIndexDocumentSimple($this->indexOptions);
//		$result[] = $this->testService->generateIndexDocuments(TestService::DOCUMENT_TEST_INDEX3);

		return $result;
	}


	/**
	 * generate documents prior to the indexing.
	 * throw NoResultException if no more result
	 *
	 * @param IndexDocument[] $chunk
	 *
	 * @return IndexDocument[]
	 */
	public function fillIndexDocuments($chunk) {
		return $chunk;
	}


	/**
	 * @param IndexDocument $document
	 *
	 * @return bool
	 */
	public function isDocumentUpToDate($document) {
		return false;
	}


	/**
	 * @param Index $index
	 *
	 * @return IndexDocument|null
	 */
	public function updateDocument(Index $index) {
		return null;
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
	 * @param SearchRequest $request
	 */
	public function improveSearchRequest(SearchRequest $request) {
	}


	/**
	 * after a search, improve results
	 *
	 * @param SearchResult $searchResult
	 */
	public function improveSearchResult(SearchResult $searchResult) {
	}


}