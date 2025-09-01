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
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Exceptions\PlatformDoesNotExistException;
use OCA\FullTextSearch\Exceptions\PlatformIsNotCompatibleException;
use OCA\FullTextSearch\Exceptions\PlatformNotSelectedException;
use OCA\FullTextSearch\Model\PlatformWrapper;
use OCP\AppFramework\Services\IAppConfig;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PlatformService
 *
 * @package OCA\FullTextSearch\Service
 */
class PlatformService {
	/** @var PlatformWrapper[] */
	private array $platforms = [];
	private ?PlatformWrapper $platform = null;
	private bool $platformsLoaded = false;

	public function __construct(
		private AppManager $appManager,
		private readonly IAppConfig $appConfig,
		private LoggerInterface $logger,
	) {
	}


	/**
	 * @param bool $silent
	 *
	 * @return PlatformWrapper
	 * @throws Exception
	 */
	public function getPlatform(bool $silent = false): PlatformWrapper {
		try {
			$this->loadPlatform();
		} catch (Exception $e) {
			if (!$silent) {
				$this->logger->warning($e->getMessage());
			}
			throw $e;
		}

		return $this->platform;
	}


	/**
	 * @return PlatformWrapper[]
	 * @throws Exception
	 */
	public function getPlatforms(): array {
		$this->loadPlatforms();

		$platforms = [];
		foreach ($this->platforms as $wrapper) {
			$class = $wrapper->getClass();
			try {
				$platform = \OCP\Server::get((string)$class);
				if (!($platform instanceof IFullTextSearchPlatform)) {
					$this->logger->warning($class . ' does not implement ' . IFullTextSearchPlatform::class);
					continue;
				}

				$wrapper->setPlatform($platform);
				$platforms[] = $wrapper;
			} catch (ContainerExceptionInterface $e) {
				/** we cycle */
				$this->logger->warning($e->getMessage());
			}

		}

		return $platforms;
	}


	/**
	 * @throws Exception
	 */
	private function loadPlatforms() {
		if ($this->platformsLoaded) {
			return;
		}

		try {
			$apps = $this->appManager->getInstalledApps();
			foreach ($apps as $appId) {
				$this->loadPlatformsFromApp($appId);
			}

			$this->platformsLoaded = true;
		} catch (Exception $e) {
			$this->logger->warning($e->getMessage());
			throw $e;
		}

	}


	/**
	 * @throws Exception
	 * @throws PlatformDoesNotExistException
	 * @throws PlatformIsNotCompatibleException
	 * @throws PlatformNotSelectedException
	 * @throws QueryException
	 */
	private function loadPlatform() {
		if ($this->platform !== null) {
			return;
		}

		$this->loadPlatforms();

		$selected = $this->getSelectedPlatform();
		$platform = OC::$server->query((string)$selected->getClass());
		if (!($platform instanceof IFullTextSearchPlatform)) {
			throw new PlatformIsNotCompatibleException(
				$selected->getClass() . ' is not a compatible FullTextSearchPlatform'
			);
		}

		$platform->loadPlatform();
		$selected->setPlatform($platform);

		$this->platform = $selected;
	}


	/**
	 * @return PlatformWrapper
	 * @throws PlatformDoesNotExistException
	 * @throws PlatformNotSelectedException
	 */
	private function getSelectedPlatform(): PlatformWrapper {
		$selected = $this->appConfig->getAppValueString(ConfigLexicon::SEARCH_PLATFORM);
		if ($selected === '') {
			throw new PlatformNotSelectedException(
				'Admin have not selected any IFullTextSearchPlatform'
			);
		}

		foreach ($this->platforms as $wrapper) {
			if ($wrapper->getClass() === $selected) {
				return $wrapper;
			}
		}

		throw new PlatformDoesNotExistException(
			'FullTextSearchPlatform ' . $selected . ' is not available'
		);
	}


	/**
	 * @param string $appId
	 */
	private function loadPlatformsFromApp(string $appId) {
		$appInfo = $this->appManager->getAppInfo($appId);
		if (!is_array($appInfo) || !key_exists('fulltextsearch', $appInfo)
			|| !is_array($appInfo['fulltextsearch'])
			|| !key_exists('platform', $appInfo['fulltextsearch'])) {
			return;
		}

		$platforms = $appInfo['fulltextsearch']['platform'];
		if (!is_array($platforms)) {
			$platforms = [$platforms];
		}

		$wrappers = [];
		foreach ($platforms as $class) {
			$wrapper = new PlatformWrapper($appId, $class);
			$wrapper->setVersion($this->appConfig->getAppValueString('installed_version'));
			$wrappers[] = $wrapper;
		}

		$this->platforms = array_merge($this->platforms, $wrappers);
	}


}
