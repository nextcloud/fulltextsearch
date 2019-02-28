<?php
declare(strict_types=1);


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
use OC\App\AppManager;
use OCA\FullTextSearch\Exceptions\PlatformDoesNotExistException;
use OCA\FullTextSearch\Exceptions\PlatformIsNotCompatibleException;
use OCA\FullTextSearch\Exceptions\PlatformNotSelectedException;
use OCA\FullTextSearch\Model\PlatformWrapper;
use OCP\AppFramework\QueryException;
use OCP\FullTextSearch\IFullTextSearchPlatform;


/**
 * Class PlatformService
 *
 * @package OCA\FullTextSearch\Service
 */
class PlatformService {


	/** @var AppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var PlatformWrapper[] */
	private $platforms = [];

	/** @var PlatformWrapper */
	private $platform;

	/** @var bool */
	private $platformsLoaded = false;


	/**
	 * ProviderService constructor.
	 *
	 * @param AppManager $appManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		AppManager $appManager, ConfigService $configService, MiscService $miscService
	) {
		$this->appManager = $appManager;

		$this->configService = $configService;
		$this->miscService = $miscService;
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
				$this->miscService->log($e->getMessage());
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
				$platform = \OC::$server->query((string)$class);
				if ($platform instanceof IFullTextSearchPlatform) {
					$wrapper->setPlatform($platform);
					$platforms[] = $wrapper;
				}
			} catch (QueryException $e) {
				/** we cycle */
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
			$this->miscService->log($e->getMessage());
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
		$platform = \OC::$server->query((string)$selected->getClass());
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
		$selected = $this->configService->getAppValue(ConfigService::SEARCH_PLATFORM);

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
			$wrapper->setVersion($this->configService->getAppVersion($appId));
			$wrappers[] = $wrapper;
		}

		$this->platforms = array_merge($this->platforms, $wrappers);
	}


}
