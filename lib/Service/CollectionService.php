<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


use OCA\FullTextSearch\Db\IndexesRequest;
use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\IURLGenerator;


class CollectionService {
	public const LOCAL = 'local';


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
	public function getCollections(): array {
		return $this->indexesRequest->getCollections(false);
	}


	/**
	 * @param string $collection
	 * @param int $length
	 *
	 * @return array
	 */
	public function getQueue(string $collection, int $length = 0): array {
		if ($length === 0) {
			$length = $this->configService->getAppValueInt(ConfigService::COLLECTION_INDEXING_LIST);
		}

		return array_map(
			function (Index $index): array {
				return $index->asSitemap($this->urlGenerator);
			},
			$this->indexesRequest->getQueuedIndexes($collection, false, $length)
		);
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
		if (strtolower($collection) === self::LOCAL) {
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
