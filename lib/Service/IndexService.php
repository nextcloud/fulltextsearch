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
use OCA\FullNextSearch\Exceptions\InterruptException;
use OCA\FullNextSearch\INextSearchPlatform;
use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\ExtendedBase;

class IndexService {


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
	 * @param ConfigService $configService
	 * @param ProviderService $providerService
	 * @param PlatformService $platformService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ConfigService $configService, ProviderService $providerService, PlatformService $platformService,
		MiscService $miscService
	) {
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

			$provider->initializeIndex($platform, $userId);
			$this->indexChunks($platform, $provider, $command);
			$provider->finalizeIndex();

			$this->providerService->setProviderAsIndexed($provider, true);
		}

	}


	/**
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param ExtendedBase $command
	 *
	 * @throws InterruptException
	 */
	private function indexChunks(
		INextSearchPlatform $platform, INextSearchProvider $provider, ExtendedBase $command
	) {

		for ($i = 0; $i < 10000; $i++) {

			try {
				$this->indexChunk($platform, $provider, $command);
			} catch (InterruptException $e) {
				throw $e;
			} catch (Exception $e) {
				return;
			}

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
	 * @param INextSearchPlatform $platform
	 * @param INextSearchProvider $provider
	 * @param ExtendedBase|null $command
	 */
	private function indexChunk(
		INextSearchPlatform $platform, INextSearchProvider $provider, $command
	) {
		$items = $provider->generateDocuments(
			(int)$this->configService->getAppValue(ConfigService::CHUNK_INDEX)
		);

		$platform->indexDocuments($provider, $items, $this->validCommand($command));
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