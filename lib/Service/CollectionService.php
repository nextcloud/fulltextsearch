<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;


use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Db\IndexesRequest;
use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCP\AppFramework\Services\IAppConfig;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\IURLGenerator;

class CollectionService {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IndexesRequest */
	private $indexesRequest;

	/** @var ProviderService */
	private $providerService;

	/** @var IndexService */
	private $indexService;

	/** @var ConfigService */
	private $configService;


	/** @var Runner */
	private $runner;


	/**
	 * @param IURLGenerator $urlGenerator
	 * @param IndexesRequest $indexesRequest
	 * @param ProviderService $providerService
	 * @param IndexService $indexService
	 * @param ConfigService $configService
	 */
	public function __construct(
		private IAppConfig $appConfig,
		IURLGenerator $urlGenerator,
		IndexesRequest $indexesRequest,
		ProviderService $providerService,
		IndexService $indexService,
		ConfigService $configService
	) {
		$this->urlGenerator = $urlGenerator;
		$this->indexesRequest = $indexesRequest;
		$this->providerService = $providerService;
		$this->indexService = $indexService;
		$this->configService = $configService;
	}


	public function setRunner(Runner $runner): void {
		$this->runner = $runner;
	}


	/**
	 * @param string $collection
	 */
	public function deleteCollection(string $collection): void {
		$this->indexesRequest->deleteCollection($collection);
	}


	/**
	 * @param string $collection
	 *
	 * @return bool
	 */
	public function hasCollection(string $collection): bool {
		foreach ($this->getCollections() as $item) {
			if ($item === $collection) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	public function getCollections(bool $local = true): array {
		return $this->indexesRequest->getCollections($local);
	}


	/**
	 * @param string $collection
	 * @param int $length
	 *
	 * @return array
	 */
	public function getQueue(string $collection, int $length = 0): array {
		if ($length === 0) {
			$length = $this->appConfig->getAppValueInt(ConfigLexicon::COLLECTION_INDEXING_LIST);
		}

		return array_map(
			function (Index $index): array {
				return $index->asSitemap($this->urlGenerator);
			},
			$this->indexesRequest->getQueuedIndexes($collection, false, $length)
		);
	}

	public function resetCollection(string $collection): void {
		$this->indexesRequest->resetCollection($collection);
	}

	/**
	 * @param string $collection
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @throws IndexDoesNotExistException
	 */
	public function setAsDone(string $collection, string $providerId, string $documentId): void {
		$index = $this->indexesRequest->getIndex($providerId, $documentId, $collection);
		$index->setStatus(IIndex::INDEX_DONE);
		$this->indexService->updateIndex($index);
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param string $collection
	 * @param string $userId
	 * @param IndexOptions $options
	 */
	public function initCollectionIndexes(
		IFullTextSearchProvider $provider,
		string $collection,
		string $userId,
		IndexOptions $options
	) {
		$chunks = $provider->generateChunks($userId);
		if (empty($chunks)) {
			$chunks = [$userId];
		}

		$this->updateRunnerInfo('chunkTotal', (string)sizeof($chunks));
		$chunkCount = 0;
		foreach ($chunks as $chunk) {
			$documents = $provider->generateIndexableDocuments($userId, (string)$chunk);
			$this->updateRunnerInfoArray(
				[
					'chunkCurr' => ++$chunkCount,
					'documentChunk' => sizeof($documents)
				]
			);

			$documentCount = 0;
			foreach ($documents as $document) {
				$this->updateRunnerInfo('documentCurr', (string)++$documentCount);
				$curr = $this->runner->getInfoInt('documentTotal');
				$this->runner->setInfoInt('documentTotal', ++$curr);

				try {
					$this->indexesRequest->getIndex(
						$document->getProviderId(),
						$document->getId(),
						$collection
					);
				} catch (IndexDoesNotExistException $e) {
					$index = new Index($document->getProviderId(), $document->getId(), $collection);
					$index->setStatus(IIndex::INDEX_FULL);
					$index->setOwnerId($document->getAccess()->getOwnerId());
					$index->setSource($document->getSource());
					$index->setLastIndex();
					$this->indexesRequest->create($index);

					$curr = $this->runner->getInfoInt('indexCount');
					$this->runner->setInfoInt('indexCount', ++$curr);
				}
			}
		}
	}


	/**
	 * @param string $collection
	 *
	 * @throws CollectionArgumentException
	 */
	public function confirmCollectionString(string $collection): void {
		if (strtolower($collection) === $this->configService->getInternalCollection()) {
			throw new CollectionArgumentException('invalid name');
		}
	}

	/**
	 * @param string $collection
	 *
	 * @throws CollectionArgumentException
	 */
	public function confirmCollection(string $collection): void {
		$this->confirmCollectionString($collection);

		if (!$this->hasCollection($collection)) {
			throw new CollectionArgumentException('collection does not exist');
		}
	}


	/**
	 * @param string $collection
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return IIndexDocument
	 * @throws ProviderDoesNotExistException
	 * @throws IndexDoesNotExistException
	 */
	public function getDocument(string $collection, string $providerId, string $documentId): IIndexDocument {
		$wrapped = $this->providerService->getProvider($providerId);
		$provider = $wrapped->getProvider();

		$index = $this->indexService->getIndex($providerId, $documentId, $collection);
		$index->setStatus(IIndex::INDEX_FULL);

		return $provider->updateDocument($index);
	}

	/**
	 * @return array
	 */
	public function getLinks(): array {
		return $this->appConfig->getAppValueArray(ConfigLexicon::COLLECTION_LINKS);
	}

	/**
	 * @param array $links
	 */
	public function saveLinks(array $links): void {
		$this->appConfig->setAppValueArray(ConfigLexicon::COLLECTION_LINKS, $links);
	}

	/**
	 * @param string $collection
	 * @param string $userId
	 */
	public function addLink(string $collection, string $userId): void {
		$links = $this->getLinks();
		$links[$collection] = $userId;
		$this->saveLinks($links);
	}

	/**
	 * @param string $collection
	 */
	public function removeLink(string $collection): void {
		$links = $this->getLinks();
		unset($links[$collection]);
		$this->saveLinks($links);
	}

	/**
	 * @param string $collection
	 *
	 * @return string
	 * @throws CollectionArgumentException
	 */
	public function getLinkedAccount(string $collection): string {
		if (!$this->hasCollection($collection)) {
			throw new CollectionArgumentException('unknown collection');
		}

		$links = $this->getLinks();
		$userId = $links[$collection] ?? '';
		if ($userId === '') {
			throw new CollectionArgumentException('no linked account');
		}

		return $userId;
	}

	/**
	 * @param string $info
	 * @param string $value
	 */
	private function updateRunnerInfo(string $info, string $value): void {
		if (is_null($this->runner)) {
			return;
		}

		$this->runner->setInfo($info, $value);
	}


	/**
	 * @param array $data
	 */
	private function updateRunnerInfoArray(array $data): void {
		if (is_null($this->runner)) {
			return;
		}

		$this->runner->setInfoArray($data);
	}

}
