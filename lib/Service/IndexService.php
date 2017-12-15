<?php
/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\FullNextSearch\Service;

use Exception;
use OC\Core\Command\Base;
use OCA\FullNextSearch\Db\IndexesRequest;
use OCA\FullNextSearch\Exceptions\DatabaseException;
use OCA\FullNextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullNextSearch\Exceptions\NoResultException;
use OCA\FullNextSearch\INextSearchPlatform;
use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\ExtendedBase;
use OCA\FullNextSearch\Model\ExtendedIndex;
use OCA\FullNextSearch\Model\Index;
use OCA\FullNextSearch\Model\IndexDocument;
use OCA\FullNextSearch\Model\ProviderIndexes;
use OCA\FullNextSearch\Model\Runner;

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
	 */
	private function updateRunner($action) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->update($action);
	}


	/**
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param string $userId
	 */
	public function indexProviderContentFromUser(
		INextSearchPlatform $platform, INextSearchProvider $provider, $userId
	) {
		$this->updateRunner('generateIndex' . $provider->getName());
		$documents = $provider->generateIndexableDocuments($userId);

		//$maxSize = sizeof($documents);

		$toIndex = $this->updateDocumentsWithCurrentIndex($provider, $documents);
		$this->indexChunks($platform, $provider, $toIndex);
	}


	/**
	 * @param INextSearchProvider $provider
	 * @param IndexDocument[] $items
	 *
	 * @return IndexDocument[]
	 */
	private function updateDocumentsWithCurrentIndex(INextSearchProvider $provider, array $items) {

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
	 * @param INextSearchProvider $provider
	 *
	 * @return ProviderIndexes
	 */
	private function getProviderIndexFromProvider(INextSearchProvider $provider) {
		$indexes = $this->indexesRequest->getIndexesFromProvider($provider);

		return new ProviderIndexes($indexes);
	}


	/**
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param IndexDocument[] $documents
	 *
	 * @throws Exception
	 */
	private function indexChunks(INextSearchPlatform $platform, INextSearchProvider $provider, $documents
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
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param IndexDocument[] $chunk
	 *
	 * @throws NoResultException
	 */
	private function indexChunk(INextSearchPlatform $platform, INextSearchProvider $provider, $chunk) {
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
						  ->isStatus(Index::STATUS_INDEX_IGNORE)) {
				$toIndex[] = $document;
			}
		}

		return $toIndex;
	}


	/**
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param Index $index
	 *
	 * @internal param int|string $documentId
	 */
	public function updateDocument(
		INextSearchPlatform $platform, INextSearchProvider $provider, Index $index
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
	 */
	private function updateIndex(Index $index) {

		if ($index->isStatus(Index::STATUS_DOCUMENT_REMOVED)) {
			$this->indexesRequest->deleteIndex($index);

		} else if (!$this->indexesRequest->update($index)) {
			$this->indexesRequest->create($index);
		}
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 * @param int $status
	 */
	public function updateIndexStatus($providerId, $documentId, $status) {
		try {
			$curr = $this->getIndex($providerId, $documentId);
		} catch (IndexDoesNotExistException $e) {
			$curr = new Index($providerId, $documentId);
			$curr->setStatus(Index::STATUS_INDEX_THIS);
		}

		$curr->setStatus($status);
		$this->updateIndexes([$curr]);
	}


	/**
	 * @param string $providerId
	 * @param string|int $documentId
	 *
	 * @return ExtendedIndex
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
			$this->indexesRequest->deleteFromProviderId($provider->getId);
		}
	}


}