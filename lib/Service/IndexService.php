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

use \Exception;
use OC\Core\Command\Base;
use OCA\FullNextSearch\Db\IndexesRequest;
use OCA\FullNextSearch\Exceptions\DatabaseException;
use OCA\FullNextSearch\Exceptions\InterruptException;
use OCA\FullNextSearch\Exceptions\NoResultException;
use OCA\FullNextSearch\INextSearchPlatform;
use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\DocumentIndex;
use OCA\FullNextSearch\Model\ExtendedBase;
use OCA\FullNextSearch\Model\ProviderIndex;
use OCA\FullNextSearch\Model\SearchDocument;

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

//				echo memory_get_usage() . "\n";

	/**
	 * @param $userId
	 * @param ExtendedBase|null $command
	 *
	 * @throws InterruptException
	 */
	public function indexContentFromUser($userId, ExtendedBase $command = null) {
		$providers = $this->providerService->getProviders();
		$platform = $this->platformService->getPlatform();

		$platform->initPlatform();
		foreach ($providers AS $provider) {

			$items = $provider->initializeIndex($platform, $userId);
			$documents = $this->removeUpToDateDocuments($provider, $items);

			$this->indexChunks($platform, $provider, $documents, $command);
			$provider->finalizeIndex();

			$this->providerService->setProviderAsIndexed($provider, true);
		}

	}


	/**
	 * @param INextSearchProvider $provider
	 * @param SearchDocument[] $items
	 *
	 * @return SearchDocument[]
	 */
	private function removeUpToDateDocuments(INextSearchProvider $provider, array $items) {

		$currIndex = $this->getProviderIndexFromProvider($provider);
		$result = [];
		foreach ($items as $item) {
			if (!$currIndex->documentIsUpToDate($item)) {
				$result[] = $item;
			}
		}

		return $result;
	}


	/**
	 * @param INextSearchProvider $provider
	 *
	 * @return ProviderIndex
	 */
	private function getProviderIndexFromProvider(INextSearchProvider $provider) {
		$indexes = $this->indexesRequest->getIndexesFromProvider($provider);

		return new ProviderIndex($indexes);
	}


	/**
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param SearchDocument[] $documents
	 * @param ExtendedBase $command
	 *
	 * @return DocumentIndex[]
	 * @throws DatabaseException
	 * @throws InterruptException
	 */
	private function indexChunks(
		INextSearchPlatform $platform, INextSearchProvider $provider, $documents, ExtendedBase $command
	) {

		$index = [];
		$chunkSize = $this->configService->getAppValue(ConfigService::CHUNK_INDEX);

		for ($i = 0; $i < 10000; $i++) {

			try {
				$chunk = array_splice($documents, 0, $chunkSize);
				$index =
					array_merge($index, $this->indexChunk($platform, $provider, $chunk, $command));
			} catch (InterruptException $e) {
				throw $e;
			} catch (DatabaseException $e) {
				throw $e;
			} catch (Exception $e) {
				return $index;
			}

		}

		return $index;
	}


	/**
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param SearchDocument[] $chunk
	 * @param ExtendedBase|null $command
	 *
	 * @return DocumentIndex[]
	 * @throws NoResultException
	 */
	private function indexChunk(
		INextSearchPlatform $platform, INextSearchProvider $provider, $chunk, $command
	) {
		if (sizeof($chunk) === 0) {
			throw new NoResultException();
		}

		$documents = $provider->generateDocuments($chunk);
		$indexes = $platform->indexDocuments($provider, $documents, $this->validCommand($command));
		$this->updateIndexes($indexes);

		return $indexes;
	}


	/**
	 * @param DocumentIndex[] $indexes
	 *
	 * @throws DatabaseException
	 */
	private function updateIndexes($indexes) {
		try {
			foreach ($indexes as $index) {
				if (!$this->indexesRequest->update($index)) {
					$this->indexesRequest->create($index);
				}
			}
		} catch (Exception $e) {
			throw new DatabaseException($e->getMessage());
		}
	}


	/**
	 * @param string $providerId
	 */
	public function resetIndex($providerId = '') {
		$platform = $this->platformService->getPlatform();

		if ($providerId === '') {
			$platform->resetPlatform(null);
			$this->providerService->setProvidersAsNotIndexed();

			return;
		} else {
			$providers = [$this->providerService->getProvider($providerId)];
		}

		foreach ($providers AS $provider) {
			$platform->resetPlatform($provider);
			$this->providerService->setProviderAsIndexed($provider, false);
		}
	}


	/**
	 * @param null|ExtendedBase $command
	 *
	 * @return null|ExtendedBase
	 */
	private function validCommand($command) {
		if ($command === null) {
			return null;
		}

		if ($command instanceof Base) {
			return $command;
		}

		return null;
	}


}