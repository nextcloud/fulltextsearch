<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use Exception;
use OC;
use OC\App\AppManager;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotUniqueException;
use OCA\FullTextSearch\Model\ProviderWrapper;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Service\IProviderService;
use OCP\ServerVersion;
use OCP\Util;
use Psr\Log\LoggerInterface;

class ProviderService implements IProviderService {
	/** @var ProviderWrapper[] */
	private array $providers = [];
	private bool $providersLoaded = false;

	public function __construct(
		private AppManager $appManager,
		private readonly IAppConfig $appConfig,
		private readonly ServerVersion $serverVersion,
		private LoggerInterface $logger,
	) {
	}


	/**
	 * Load all FullTextSearchProviders set in any info.xml file
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
			$this->logger->warning('could not load providers', ['exception' => $e]);
		}

		$this->providersLoaded = true;
	}


	/**
	 * @param string $appId
	 * @param string $providerId
	 *
	 * @throws ProviderIsNotCompatibleException
	 * @throws ProviderIsNotUniqueException
	 * @throws QueryException
	 */
	public function loadProvider(string $appId, string $providerId) {
		$provider = OC::$server->query((string)$providerId);
		if (!($provider instanceof IFullTextSearchProvider)) {
			throw new ProviderIsNotCompatibleException($providerId . ' is not a compatible IFullTextSearchProvider');
		}

		$this->providerIdMustBeUnique($provider);
		try {
			$provider->loadProvider();
			$wrapper = new ProviderWrapper($appId, $provider);
			$wrapper->setVersion($this->appConfig->getAppValueString('installed_version'));
			$this->providers[] = $wrapper;
		} catch (Exception $e) {
		}
	}

	/**
	 * @return ProviderWrapper[]
	 * @throws Exception
	 */
	public function getProviders(): array {
		$this->loadProviders();

		return $this->providers;
	}

	/**
	 * @return IFullTextSearchProvider[]
	 * @throws Exception
	 */
	public function getConfiguredProviders(): array {
		$this->loadProviders();

		$providers = [];
		foreach ($this->providers as $providerWrapper) {
			$providers[] = $providerWrapper->getProvider();
		}

		return $providers;
	}


	/**
	 * @param array $providerList
	 *
	 * @return IFullTextSearchProvider[]
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function getFilteredProviders(array $providerList): array {
		$this->loadProviders();

		$providers = $this->getConfiguredProviders();
		if (in_array('all', $providerList)) {
			return $providers;
		}

		$ret = [];
		foreach ($providerList as $providerId) {
			$providerWrapper = $this->getProvider($providerId);
			$ret[] = $providerWrapper->getProvider();
		}

		return $ret;
	}


	/**
	 * @param string $providerId
	 *
	 * @return ProviderWrapper
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function getProvider(string $providerId): ProviderWrapper {
		$providers = $this->getProviders();
		foreach ($providers as $providerWrapper) {
			$provider = $providerWrapper->getProvider();
			if ($provider->getId() === $providerId) {
				return $providerWrapper;
			}
		}

		throw new ProviderDoesNotExistException('Provider \'' . $providerId . '\' does not exist');
	}


	/**
	 * @param string $providerId
	 *
	 * @return bool
	 * @deprecated will always return true to fit OC\FullTextSearch\FullTextSearchManager
	 */
	public function isProviderIndexed(string $providerId): bool {
		return true;
	}


	/**
	 * @param string $appId
	 */
	private function loadProvidersFromApp(string $appId) {
		$appInfo = $this->appManager->getAppInfo($appId);
		if (!is_array($appInfo) || !array_key_exists('fulltextsearch', $appInfo)
			|| !is_array($appInfo['fulltextsearch'])
			|| !key_exists('provider', $appInfo['fulltextsearch'])) {
			return;
		}

		$providers = $appInfo['fulltextsearch']['provider'];
		if (!is_array($providers)) {
			$providers = [$providers];
		}

		$this->loadProvidersFromList($appId, $providers);
	}


	/**
	 * @param string $appId
	 * @param array $providers
	 */
	private function loadProvidersFromList(string $appId, array $providers) {
		if (array_key_exists('@attributes', $providers)) {
			$providers = [$providers];
		}
		foreach ($providers as $provider) {
			if (is_array($provider)) {
				$attributes = $provider['@attributes'];
				if (array_key_exists('min-version', $attributes)
					&& $this->serverVersion->getMajorVersion() < (int)$attributes['min-version']) {
					continue;
				}
				if (array_key_exists('max-version', $attributes)
					&& $this->serverVersion->getMajorVersion() > (int)$attributes['max-version']) {
					continue;
				}

				$provider = $provider['@value'];
			}

			try {
				$this->loadProvider($appId, $provider);
			} catch (Exception $e) {
				$this->logger->warning('Issue while loading Provider: ' . $appId . '/' . $provider, ['exception' => $e]);
			}
		}
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 *
	 * @throws ProviderIsNotUniqueException
	 * @throws Exception
	 */
	private function providerIdMustBeUnique(IFullTextSearchProvider $provider) {
		foreach ($this->providers as $providerWrapper) {
			$knownProvider = $providerWrapper->getProvider();
			if ($knownProvider->getId() === $provider->getId()) {
				throw new ProviderIsNotUniqueException(
					'FullTextSearchProvider ' . $provider->getId() . ' already exist'
				);
			}
		}
	}


	/**
	 * @param IFullTextSearchProvider[] $providers
	 *
	 * @return array
	 */
	public function serialize(array $providers): array {
		$arr = [];
		foreach ($providers as $provider) {
			$arr[] = [
				'id' => $provider->getId(),
				'name' => $provider->getName()
			];
		}

		return $arr;
	}


	/**
	 *
	 */
	public function addJavascriptAPI() {
		Util::addStyle(Application::APP_ID, 'fulltextsearch');
		Util::addScript(Application::APP_ID, 'fulltextsearch.v1.api');
		Util::addScript(Application::APP_ID, 'fulltextsearch.v1.settings');
		Util::addScript(Application::APP_ID, 'fulltextsearch.v1.searchbox');
		Util::addScript(Application::APP_ID, 'fulltextsearch.v1.result');
		Util::addScript(Application::APP_ID, 'fulltextsearch.v1.navigation');
		Util::addScript(Application::APP_ID, 'fulltextsearch.v1');
	}


}
