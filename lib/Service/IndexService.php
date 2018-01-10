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

namespace OCA\FullTextSearch\Service;

use Exception;
use OCA\FullTextSearch\Db\IndexesRequest;
use OCA\FullTextSearch\Exceptions\DatabaseException;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Exceptions\InterruptException;
use OCA\FullTextSearch\Exceptions\NoResultException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\IFullTextSearchPlatform;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\ExtendedIndex;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Model\ProviderIndexes;
use OCA\FullTextSearch\Model\Runner;

class IndexService {

	/** @var IndexesRequest */
	private $indexesRequest;

	/** @var ConfigService */
	private $configService;

	/** @var ProviderService */
	private $providerService;

	/** @var PlatformService */
	private $platformService;

	/** @var MiscService */
	private $miscService;


	/** @var Runner */
	private $runner = null;

	/**
	 * IndexService constructor.
	 *
	 * @param IndexesRequest $indexesRequest
	 * @param ConfigService $configService
	 * @param ProviderService $providerService
	 * @param PlatformService $platformService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IndexesRequest $indexesRequest, ConfigService $configService, ProviderService $providerService,
		PlatformService $platformService, MiscService $miscService
	) {
		$this->indexesRequest = $indexesRequest;
		$this->configService = $configService;
		$this->providerService = $providerService;
		$this->platformService = $platformService;
		$this->miscService = $miscService;
	}


	/**
	 * @param Runner $runner
	 */
	public function setRunner(Runner $runner) {
		$this->runner = $runner;
	}


	/**
	 * @param $action
	 *
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	private function updateRunner($action) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->update($action);
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param string $userId
	 *
	 * @throws Exception
	 */
	public function indexProviderContentFromUser(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, $userId
	) {
		$this->updateRunner('generateIndex' . $provider->getName());
		$documents = $provider->generateIndexableDocuments($userId);

		//$maxSize = sizeof($documents);

		$toIndex = $this->updateDocumentsWithCurrentIndex($provider, $documents);
		$this->indexChunks($platform, $provider, $toIndex);
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument[] $items
	 *
	 * @return IndexDocument[]
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	private function updateDocumentsWithCurrentIndex(IFullTextSearchProvider $provider, array $items) {

		$currIndex = $this->getProviderIndexFromProvider($provider);
		$result = [];
		foreach ($items as $item) {
			$this->updateRunner('compareWithCurrentIndex');
			if (!$currIndex->isDocumentIndexUpToDate($item)) {
				$result[] = $item;
			}

		}

		return $result;
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 *
	 * @return ProviderIndexes
	 */
	private function getProviderIndexFromProvider(IFullTextSearchProvider $provider) {
		$indexes = $this->indexesRequest->getIndexesFromProvider($provider);

		return new ProviderIndexes($indexes);
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument[] $documents
	 *
	 * @throws Exception
	 */
	private function indexChunks(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, $documents
	) {
		$chunkSize = $this->configService->getAppValue(ConfigService::CHUNK_INDEX);

		$max = sizeof($documents);
		for ($i = 0; $i < $max; $i++) {

			$this->updateRunner('indexChunk');
			try {
				$chunk = array_splice($documents, 0, $chunkSize);
				$this->indexChunk($platform, $provider, $chunk);

				/** @var IndexDocument $doc */
				foreach ($chunk as $doc) {
					$doc->__destruct(); // because.
				}
			} catch (NoResultException $e) {
				return;
			} catch (Exception $e) {
				throw $e;
			}
		}
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument[] $chunk
	 *
	 * @throws NoResultException
	 * @throws DatabaseException
	 */
	private function indexChunk(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, $chunk
	) {
		if (sizeof($chunk) === 0) {
			throw new NoResultException();
		}

		$documents = $provider->fillIndexDocuments($chunk);
		$toIndex = $this->filterDocumentsToIndex($documents);
		$indexes = $platform->indexDocuments($provider, $toIndex);
		$this->updateIndexes($indexes);
	}


	/**
	 * @param IndexDocument[] $documents
	 *
	 * @return array
	 */
	private function filterDocumentsToIndex($documents) {
		$toIndex = [];
		foreach ($documents as $document) {
			// TODO - rework the index/not_index
//			echo '==st==' . $document->getIndex()->getStatus() . "\n";
			if (!$document->getIndex()
						  ->isStatus(Index::INDEX_IGNORE)) {
				$toIndex[] = $document;
			}
		}

		return $toIndex;
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param Index $index
	 *
	 * @internal param int|string $documentId
	 * @throws Exception
	 */
	public function updateDocument(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, Index $index
	) {
		$document = $provider->updateDocument($index);
		if ($document === null) {
			$this->indexesRequest->deleteIndex($index);

			return;
		}

		$index = $platform->indexDocument($provider, $document);
		$this->updateIndex($index);
	}


	/**
	 * @param Index[] $indexes
	 *
	 * @throws DatabaseException
	 */
	public function updateIndexes($indexes) {

		try {
			foreach ($indexes as $index) {
				$this->updateIndex($index);
			}
		} catch (Exception $e) {
			throw new DatabaseException($e->getMessage());
		}
	}


	/**
	 * @param Index $index
	 *
	 * @throws Exception
	 */
	private function updateIndex(Index $index) {

		if ($index->isStatus(Index::INDEX_REMOVE)) {

			if ($index->isStatus(Index::INDEX_OK)) {
				$this->indexesRequest->deleteIndex($index);

				return;
			}
			$index->setStatus(Index::INDEX_FAILED);
			$this->indexesRequest->update($index);

			return;
		}

		if (!$this->indexesRequest->update($index)) {
			$this->indexesRequest->create($index);
		}
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 * @param int $status
	 *
	 * @throws DatabaseException
	 */
	public function updateIndexStatus($providerId, $documentId, $status) {
		try {
			$curr = $this->getIndex($providerId, $documentId);
		} catch (IndexDoesNotExistException $e) {
			$curr = new Index($providerId, $documentId);
			$curr->setStatus(Index::INDEX_ALL);
		}

		$curr->setStatus($status);
		$this->updateIndexes([$curr]);
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 *
	 * @return ExtendedIndex
	 * @throws IndexDoesNotExistException
	 */
	public function getIndex($providerId, $documentId) {
		return $this->indexesRequest->getIndex($providerId, $documentId);
	}


	/**
	 * @return Index[]
	 */
	public function getQueuedIndexes() {
		return $this->indexesRequest->getQueuedIndexes();
	}


	/**
	 * @param string $providerId
	 *
	 * @throws Exception
	 */
	public function resetIndex($providerId = '') {
		$platform = $this->platformService->getPlatform();

		if ($providerId === '') {
			$platform->removeIndex(null);
			$this->providerService->setProvidersAsNotIndexed();
			$this->indexesRequest->reset();

			return;
		} else {
			$providers = [$this->providerService->getProvider($providerId)];
		}

		foreach ($providers AS $provider) {
			$platform->removeIndex($provider);
			$this->providerService->setProviderAsIndexed($provider, false);
			$this->indexesRequest->deleteFromProviderId($provider->getId());
		}
	}


}