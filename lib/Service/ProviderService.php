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
use OC\App\AppManager;
use OC_App;
use OCA\FullNextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullNextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullNextSearch\Exceptions\ProviderIsNotUniqueException;
use OCA\FullNextSearch\Exceptions\ProviderOptionsDoesNotExistException;
use OCA\FullNextSearch\INextSearchProvider;

class ProviderService {

	/** @var AppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var INextSearchProvider[] */
	private $providers = [];

	/** @var bool */
	private $providersLoaded = false;


	/**
	 * ProviderService constructor.
	 *
	 * @param AppManager $appManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 *
	 */
	public function __construct(
		AppManager $appManager, ConfigService $configService, MiscService $miscService
	) {
		$this->appManager = $appManager;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * Load all NextSearchProviders set in any info.xml file
	 *
	 * @throws Exception
	 */
	private function loadProviders() {
		if ($this->providersLoaded) {
			return;
		}

		try {
			$apps = $this->appManager->getInstalledApps();
			foreach ($apps as $appId) {
				$this->loadProvidersFromApp($appId);
			}
		} catch (Exception $e) {
			$this->miscService->log($e->getMessage());
		}

		$this->providersLoaded = true;
	}


	/**
	 * @param string $providerId
	 *
	 * @throws ProviderIsNotCompatibleException
	 */
	public function loadProvider($providerId) {

		$provider = \OC::$server->query((string)$providerId);
		if (!($provider instanceof INextSearchProvider)) {
			throw new ProviderIsNotCompatibleException(
				$providerId . ' is not a compatible NextSearchProvider'
			);
		}

		$this->providerIdMustBeUnique($provider);

		$provider->loadProvider();
		$this->providers[] = $provider;
	}


	/**
	 * @return INextSearchProvider[]
	 */
	public function getProviders() {
		$this->loadProviders();

		return $this->providers;
	}


	/**
	 * @param string $providerId
	 *
	 * @return INextSearchProvider[]
	 */
	public function getFilteredProviders($providerId) {
		$this->loadProviders();

		$providers = $this->getProviders();
		if ($providerId === '_all') {
			return $providers;
		}

		return [$this->getProvider($providerId)];
	}


	/**
	 * @param string $providerId
	 *
	 * @return INextSearchProvider
	 * @throws ProviderDoesNotExistException
	 */
	public function getProvider($providerId) {

		$providers = $this->getProviders();
		foreach ($providers as $provider) {
			if ($provider->getId() === $providerId) {
				return $provider;
			}
		}

		throw new ProviderDoesNotExistException('Provider \'' . $providerId . '\' does not exist');
	}


	/**
	 * @param string $providerId
	 *
	 * @return bool
	 */
	public function isProviderIndexed($providerId) {
		try {
			$indexed = $this->configService->getProviderOptions(
				$providerId, ConfigService::PROVIDER_INDEXED
			);
		} catch (ProviderOptionsDoesNotExistException $e) {
			return false;
		}

		if ($indexed === '1') {
			return true;
		}

		return false;

	}


	public function setProviderAsIndexed(INextSearchProvider $provider, $boolean) {
		$this->configService->setProviderOptions(
			$provider->getId(), ConfigService::PROVIDER_INDEXED, (($boolean) ? '1' : '0')
		);
	}


	public function setProvidersAsNotIndexed() {
		$this->configService->resetProviderOptions(ConfigService::PROVIDER_INDEXED);
	}


	/**
	 * @param string $appId
	 *
	 * @throws ProviderIsNotUniqueException
	 */
	private function loadProvidersFromApp($appId) {
		$appInfo = OC_App::getAppInfo($appId);
		if (!is_array($appInfo) || !key_exists('fullnextsearch', $appInfo)
			|| !key_exists('provider', $appInfo['fullnextsearch'])) {
			return;
		}

		$providers = $appInfo['fullnextsearch']['provider'];
		$this->loadProvidersFromList($providers);
	}


	/**
	 * @param string|array $providers
	 */
	private function loadProvidersFromList($providers) {
		if (!is_array($providers)) {
			$providers = [$providers];
		}

		foreach ($providers AS $provider) {
			$this->loadProvider($provider);
		}
	}


	/**
	 * @param INextSearchProvider $provider
	 *
	 * @throws ProviderIsNotUniqueException
	 */
	private function providerIdMustBeUnique(INextSearchProvider $provider) {
		foreach ($this->providers AS $knownProvider) {
			if ($knownProvider->getId() === $provider->getId()) {
				throw new ProviderIsNotUniqueException(
					'NextSearchProvider ' . $provider->getId() . ' already exist'
				);
			}
		}
	}
}