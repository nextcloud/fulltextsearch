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
use OCA\FullTextSearch\Model\IndexOptions;
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
		IndexesRequest $indexesRequest, ConfigService $configService,
		ProviderService $providerService,
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
	 * @param string $action
	 * @param bool $force
	 *
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	private function updateRunnerAction($action, $force = false) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->updateAction($action, $force);
	}

	/**
	 * @param string $info
	 * @param string $value
	 */
	private function updateRunnerInfo($info, $value) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->setInfo($info, $value);
	}

	/**
	 * @param array $data
	 */
	private function updateRunnerInfoArray($data) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->setInfoArray($data);
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param string $userId
	 * @param IndexOptions $options
	 *
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 * @throws Exception
	 */
	public function indexProviderContentFromUser(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, $userId, $options
	) {
		$this->updateRunnerAction('generateIndex' . $provider->getName());
		$this->updateRunnerInfoArray(
			[
				'userId'       => $userId,
				'providerId'   => $provider->getId(),
				'providerName' => $provider->getName()
			]
		);

		$documents = $provider->generateIndexableDocuments($userId);

		$this->updateRunnerInfoArray(
			[
				'documentTotal' => sizeof($documents),
				'documentLeft'  => ''
			]
		);

		//$maxSize = sizeof($documents);

		$toIndex = $this->updateDocumentsWithCurrIndex($provider, $documents, $options);
		$this->indexChunks($platform, $provider, $toIndex);

//		$this->updateRunnerInfoArray(
//			[
//				'documentLeft' => 0
//			]
//		);
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument[] $documents
	 * @param IndexOptions $options
	 *
	 * @return IndexDocument[]
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	private function updateDocumentsWithCurrIndex(
		IFullTextSearchProvider $provider, array $documents, $options
	) {

		$currIndex = $this->getProviderIndexFromProvider($provider);
		$result = [];
		foreach ($documents as $document) {
			$this->updateRunnerAction('compareWithCurrentIndex');

			$index = $currIndex->getIndex($document->getId());
			if ($index === null) {
				$index = new Index($document->getProviderId(), $document->getId());
				$index->setStatus(Index::INDEX_FULL);
				$index->setLastIndex();
			}

			if ($options->getOption('force', false) === true) {
				$index->setStatus(Index::INDEX_FULL);
			}

			$document->setIndex($index);
			if ($options->getOption('force', false) === true
				|| !$this->isDocumentUpToDate($provider, $document)) {
				$result[] = $document;
			}
		}

		return $result;
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument $document
	 *
	 * @return bool
	 */
	private function isDocumentUpToDate(IFullTextSearchProvider $provider, IndexDocument $document
	) {
		$index = $document->getIndex();

		if (!$index->isStatus(Index::INDEX_OK)) {
			return false;
		}

		if ($index->isStatus(Index::INDEX_META) || $index->isStatus(Index::INDEX_CONTENT)) {
			return false;
		}

		return $provider->isDocumentUpToDate($document);
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
			$this->updateRunnerAction('indexChunk', true);
			try {

				$chunk = array_splice($documents, 0, $chunkSize);
				$this->indexChunk($platform, $provider, $chunk);

				$this->updateRunnerInfoArray(['documentLeft' => sizeof($documents)]);

				/** @var IndexDocument $doc */
				foreach ($chunk as $doc) {
					$doc->__destruct(); // because.
				}
			} catch (NoResultException $e) {
				$this->updateRunnerInfoArray(['documentLeft' => 0]);

				return;
			} catch (Exception $e) {
				throw $e;
			}
		}

		$this->updateRunnerAction('indexChunk', true);
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
			$index = $document->getIndex();
			$access = $document->getAccess();

			if ($access !== null && !$index->isStatus(Index::INDEX_IGNORE)) {
				$index->setOwnerId($access->getOwnerId());
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
		$document = null;
		if (!$index->isStatus(Index::INDEX_REMOVE)) {
			try {
				$document = $provider->updateDocument($index);
			} catch (Exception $e) {
				/** we do nothing, because we're not sure provider manage the right MissingDocumentException */
			}
		}

		if ($document === null) {
			$platform->deleteIndexes([$index]);
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

			if ($index->isStatus(Index::INDEX_DONE)) {
				$this->indexesRequest->deleteIndex($index);

				return;
			}

			$this->indexesRequest->update($index);

			return;
		}

		if ($index->isStatus(Index::INDEX_DONE)) {
			$index->setStatus(Index::INDEX_OK, true);
		}

		if (!$this->indexesRequest->update($index)) {
			$this->indexesRequest->create($index);
		}
	}


	/**
	 * @param string $providerId
	 * @param array $documentIds
	 * @param int $status
	 * @param bool $reset
	 *
	 * @throws DatabaseException
	 */
	public function updateIndexesStatus($providerId, $documentIds, $status, $reset = false) {
		if ($reset === true) {
			$this->indexesRequest->updateStatus($providerId, $documentIds, $status);

			return;
		}

		try {
			$all = $this->getIndexes($providerId, $documentIds);
		} catch (IndexDoesNotExistException $e) {
			return;
		}

		foreach ($all as $curr) {
			$curr->setStatus($status);
			$this->updateIndexes([$curr]);
		}

	}


	/**
	 * @param string $providerId
	 * @param array $documentId
	 *
	 * @return ExtendedIndex[]
	 * @throws IndexDoesNotExistException
	 */
	public function getIndexes($providerId, $documentId) {
		return $this->indexesRequest->getIndexes($providerId, $documentId);
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
			$platform->resetIndex('all');
			$this->providerService->setProvidersAsNotIndexed();
			$this->indexesRequest->reset();

			return;
		} else {
			$providers = [$this->providerService->getProvider($providerId)];
		}

		foreach ($providers AS $provider) {
			// TODO: need to specify the map to remove
			// TODO: need to remove entries with type=providerId
//			$provider->onResettingIndex($platform);

			$platform->resetIndex($provider->getId());
			$this->providerService->setProviderAsIndexed($provider, false);
			$this->indexesRequest->deleteFromProviderId($provider->getId());
		}
	}


}