<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;


use Exception;
use OCA\FullTextSearch\Db\IndexesRequest;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Exceptions\NotIndexableDocumentException;
use OCA\FullTextSearch\Exceptions\PlatformTemporaryException;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\IIndexOptions;
use OCP\FullTextSearch\Model\IRunner;
use OCP\FullTextSearch\Service\IIndexService;


/**
 * Class IndexService
 *
 * @package OCA\FullTextSearch\Service
 */
class IndexService implements IIndexService {


	/** @var IndexesRequest */
	private $indexesRequest;

	/** @var ProviderService */
	private $providerService;

	/** @var PlatformService */
	private $platformService;


	/** @var Runner */
	private $runner = null;

	/** @var array */
	private $queuedDeleteIndex = [];

	/** @var int */
	private $currentTotalDocuments = 0;


	/**
	 * IndexService constructor.
	 *
	 * @param IndexesRequest $indexesRequest
	 * @param ProviderService $providerService
	 * @param PlatformService $platformService
	 */
	public function __construct(
		IndexesRequest $indexesRequest,
		ProviderService $providerService,
		PlatformService $platformService
	) {
		$this->indexesRequest = $indexesRequest;
		$this->providerService = $providerService;
		$this->platformService = $platformService;
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
	 * @throws Exception
	 */
	private function updateRunnerAction(string $action, bool $force = false) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->updateAction($action, $force);
	}

	/**
	 * @param string $info
	 * @param string $value
	 * @param int $color
	 */
	private function updateRunnerInfo(
		string $info, string $value, int $color = IRunner::RESULT_TYPE_SUCCESS
	) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->setInfo($info, $value, $color);
	}

	/**
	 * @param array $data
	 */
	private function updateRunnerInfoArray(array $data) {
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
	 * @throws Exception
	 */
	public function indexProviderContentFromUser(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, string $userId,
		IndexOptions $options
	) {
		$this->updateRunnerAction('generateIndex' . $provider->getName());
		$this->updateRunnerInfoArray(
			[
				'userId' => $userId,
				'providerId' => $provider->getId(),
				'providerName' => $provider->getName(),
				'chunkCurrent' => 0,
				'chunkTotal' => 0,
				'documentCurrent' => 0,
				'documentTotal' => 0,
				'info' => '',
				'title' => ''
			]
		);

		$chunks = $provider->generateChunks($userId);
		if (empty($chunks)) {
			$chunks = [$userId];
		}

		$this->updateRunnerInfo('chunkTotal', (string)count($chunks));
		$curr = 0;
		foreach ($chunks as $chunk) {
			$this->updateRunnerInfo('chunkCurrent', (string)++$curr);

			$documents = $provider->generateIndexableDocuments($userId, (string)$chunk);
			$this->currentTotalDocuments = sizeof($documents);
			$this->updateRunnerInfoArray(
				[
					'documentTotal' => $this->currentTotalDocuments,
					'documentCurrent' => 0
				]
			);

			//$maxSize = sizeof($documents);

			$toIndex = $this->updateDocumentsWithCurrIndex($provider, '', $documents, $options);
			$this->indexDocuments($platform, $provider, $toIndex, $options);
		}
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IIndexDocument[] $documents
	 * @param IIndexOptions $options
	 *
	 * @return IIndexDocument[]
	 * @throws Exception
	 */
	public function updateDocumentsWithCurrIndex(
		IFullTextSearchProvider $provider,
		string $collection,
		array $documents,
		IIndexOptions $options
	): array {

		$result = [];
		$count = 0;
		foreach ($documents as $document) {
			if ($count % 1000 === 0) {
				$this->updateRunnerAction('compareWithCurrentIndex', true);
				$this->updateRunnerInfo('documentCurrent', (string)$count);
			}
			$count++;

			try {
				$index = $this->indexesRequest->getIndex(
					$document->getProviderId(),
					$document->getId(),
					$collection
				);
			} catch (IndexDoesNotExistException $e) {
				$index = new Index($document->getProviderId(), $document->getId(), $collection);
				$index->setStatus(Index::INDEX_FULL);
				$index->setLastIndex();
			}

			if ($index->isStatus(IIndex::INDEX_IGNORE)) {
				continue;
			}

			if ($options->getOption('errors', '') !== 'ignore' && $index->getErrorCount() > 0) {
				continue;
			}

			if ($options->getOptionBool('force', false) === true) {
				$index->setStatus(Index::INDEX_FULL);
			}

			$index->resetErrors();
			$document->setIndex($index);
			if ($options->getOptionBool('force', false) === true
				|| !$this->isDocumentUpToDate($provider, $document)) {
				$result[] = $document;
			}
		}

		return $result;
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IIndexDocument $document
	 *
	 * @return bool
	 */
	private function isDocumentUpToDate(
		IFullTextSearchProvider $provider,
		IIndexDocument $document
	): bool {
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
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param IIndexDocument[] $documents
	 * @param IndexOptions $options
	 *
	 * @throws Exception
	 */
	private function indexDocuments(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, array $documents,
		IndexOptions $options
	) {
		while ($document = array_shift($documents)) {
			try {

				$this->updateRunnerInfoArray(
					[
						'documentCurrent' => ($this->currentTotalDocuments - sizeof($documents) - 1)
					]
				);
				$this->updateRunnerAction('fillDocument', true);
				$this->updateRunnerInfoArray(
					[
						'documentId' => $document->getId(),
						'info' => '',
						'title' => '',
						'content' => '',
						'status' => '',
						'statusColored' => ''
					]
				);

				$provider->fillIndexDocument($document);
				$this->updateRunnerInfoArray(
					[
						'title' => $document->getTitle(),
						'content' => $document->getContentSize()
					]
				);
				$this->filterDocumentBeforeIndex($document);

				$index = $this->indexDocument($platform, $document);
				$this->updateIndex($index);
			} catch (PlatformTemporaryException $e) {
				throw $e;
			} catch (Exception $e) {
			}

			$document->__destruct();
			unset($document);
		}
	}


	/**
	 * @param IIndexDocument $document
	 *
	 * @throws NotIndexableDocumentException
	 */
	private function filterDocumentBeforeIndex(IIndexDocument $document) {
		// TODO - rework the index/not_index
		$index = $document->getIndex();
		$access = $document->getAccess();

		if ($access === null || $index->isStatus(Index::INDEX_IGNORE)) {
			throw new NotIndexableDocumentException();
		}

		$index->setOwnerId($access->getOwnerId());
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IIndexDocument $document
	 *
	 * @return IIndex
	 * @throws Exception
	 */
	public function indexDocument(IFullTextSearchPlatform $platform, IIndexDocument $document
	): IIndex {
		$this->updateRunnerAction('indexDocument', true);
		$this->updateRunnerInfoArray(
			[
				'documentId' => $document->getId(),
				'title' => $document->getTitle(),
				'content' => $document->getContentSize()
			]
		);

		try {
			return $platform->indexDocument($document);
		} catch (PlatformTemporaryException $e) {
			throw $e;
		} catch (Exception $e) {
			throw new IndexDoesNotExistException();
		}
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 * @param Index $index
	 *
	 * @throws PlatformTemporaryException
	 * @throws Exception
	 */
	public function updateDocument(
		IFullTextSearchPlatform $platform, IFullTextSearchProvider $provider, Index $index
	) {
		$document = null;
		$this->updateRunnerInfoArray(
			[
				'providerName' => $provider->getName(),
				'userId' => $index->getOwnerId(),
			]
		);

		if ($index->isStatus(IIndex::INDEX_IGNORE)) {
			return;
		}

		if (!$index->isStatus(Index::INDEX_REMOVE)) {
			try {
				$document = $provider->updateDocument($index);
				if (!$document->hasIndex()) {
					$document->setIndex($index);
				}
			} catch (Exception $e) {
				/** we do nothing, because we're not sure provider manage the right MissingDocumentException */
			}
		}

		if ($document === null) {
			$platform->deleteIndexes([$index]);
			$this->indexesRequest->deleteIndex($index);

			return;
		}

		$this->updateRunnerAction('indexDocument', true);
		$this->updateRunnerInfoArray(
			[
				'documentId' => $document->getId(),
				'title' => $document->getTitle(),
				'content' => $document->getContentSize()
			]
		);

		$document->getIndex()
				 ->resetErrors();
		$index = $platform->indexDocument($document);
		$this->updateIndex($index);
	}


	/**
	 * @param Index[] $indexes
	 */
	public function updateIndexes(array $indexes) {
		foreach ($indexes as $index) {
			$this->updateIndex($index);
		}
		$this->resetErrorFromQueue();
	}


	/**
	 * @param IIndex $index
	 */
	public function updateIndex(IIndex $index) {
		/** @var Index $index */
		$this->updateIndexError($index);
		if ($index->isStatus(IIndex::INDEX_REMOVE)) {

			if ($index->isStatus(IIndex::INDEX_DONE)) {
				$this->indexesRequest->deleteIndex($index);

				return;
			}

			$this->indexesRequest->update($index);

			return;
		}

		if ($index->isStatus(IIndex::INDEX_DONE)) {
			$index->setStatus(IIndex::INDEX_OK, true);
		}

		try {
			$this->indexesRequest->getIndex($index->getProviderId(), $index->getDocumentId(), $index->getCollection());
			$this->indexesRequest->update($index);
		} catch (IndexDoesNotExistException $e) {
			$this->indexesRequest->create($index);
		}
	}


	/**
	 * @param IIndex $index
	 */
	private function updateIndexError(IIndex $index) {

	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 * @param int $status
	 * @param bool $reset
	 *
	 * @throws Exception
	 */
	public function updateIndexStatus(
		string $providerId,
		string $documentId,
		int $status,
		bool $reset = false
	) {
		$indexes = $this->indexesRequest->getIndexes($providerId, $documentId);
		foreach ($indexes as $index) {
			$index->setStatus($status);
			$this->updateIndex($index);
		}
	}


	/**
	 * @param string $providerId
	 * @param array $documentIds
	 * @param int $status
	 * @param bool $reset
	 */
	public function updateIndexesStatus(
		string $providerId,
		array $documentIds,
		int $status,
		bool $reset = false
	) {
		foreach ($documentIds as $documentId) {
			try {
				$this->updateIndexStatus($providerId, $documentId, $status, $reset);
			} catch (Exception $e) {
			}
		}
	}


	/**
	 * @param Index $index
	 */
	public function resetErrorFromIndex(Index $index) {
		if (!$this->indexesRequest->resetError($index)) {
			$this->queuedDeleteIndex[] = $index;
		}
	}


	/**
	 *
	 */
	private function resetErrorFromQueue() {
		foreach ($this->queuedDeleteIndex as $index) {
			$this->indexesRequest->resetError($index);
		}
	}

	/**
	 *
	 */
	public function resetErrorsAll() {
		$this->indexesRequest->resetAllErrors();
	}


	/**
	 * @return Index[]
	 */
	public function getErrorIndexes(): array {
		return $this->indexesRequest->getErrorIndexes();
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return Index[]
	 */
	public function getIndexes(string $providerId, string $documentId): array {
		return $this->indexesRequest->getIndexes($providerId, $documentId);
	}


	/**
	 * @param string $collection
	 * @param bool $all
	 * @param int $length
	 *
	 * @return Index[]
	 */
	public function getQueuedIndexes(string $collection = '', bool $all = false, int $length = -1): array {
		return $this->indexesRequest->getQueuedIndexes($collection, $all, $length);
	}


	/**
	 * @param string $providerId
	 * @param string $collection
	 *
	 * @throws ProviderDoesNotExistException
	 */
	public function resetIndex(string $providerId = '', string $collection = '') {
		$wrapper = $this->platformService->getPlatform();
		$platform = $wrapper->getPlatform();
		if ($providerId === '') {
			$platform->resetIndex('all');
			$this->indexesRequest->reset($collection);

			return;
		}

		$providerWrapper = $this->providerService->getProvider($providerId);
		$provider = $providerWrapper->getProvider();
//			$provider->onResettingIndex($platform);

		$platform->resetIndex($provider->getId());
		$this->indexesRequest->deleteFromProviderId($provider->getId(), $collection);
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return IIndex
	 * @throws IndexDoesNotExistException
	 */
	public function getIndex(string $providerId, string $documentId, string $collection = ''): IIndex {
		return $this->indexesRequest->getIndex($providerId, $documentId, $collection);
	}


	/**
	 * @param string $providerId
	 * @param string $documentId
	 * @param string $userId
	 * @param int $status
	 *
	 * @return IIndex
	 * @throws IndexDoesNotExistException
	 */
	public function createIndex(
		string $providerId,
		string $documentId,
		string $userId,
		int $status
	): IIndex {
		$index = null;
		foreach ($this->indexesRequest->getCollections() as $collection) {
			try {
				$index = $this->indexesRequest->getIndex($providerId, $documentId, $collection);
				$index->setStatus($status, true);
				$this->indexesRequest->update($index, true);
			} catch (IndexDoesNotExistException $e) {
				$index = new Index($providerId, $documentId, $collection);
				$index->setOwnerId($userId);
				$index->setStatus($status);
				$this->indexesRequest->create($index);
			}
		}

		if (is_null($index)) {
			throw new IndexDoesNotExistException();
		}

		$index->setCollection('');

		return $index;
	}

}
