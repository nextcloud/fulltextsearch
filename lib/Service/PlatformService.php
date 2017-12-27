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
use OCA\FullNextSearch\Exceptions\PlatformDoesNotExistException;
use OCA\FullNextSearch\Exceptions\PlatformIsNotCompatibleException;
use OCA\FullNextSearch\Exceptions\PlatformNotSelectedException;
use OCA\FullNextSearch\INextSearchPlatform;
use OCP\AppFramework\QueryException;

class PlatformService {

	/** @var AppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var array */
	private $platforms = [];

	/** @var INextSearchPlatform */
	private $platform;

	/** @var bool */
	private $platformsLoaded = false;


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
	 * @param bool $silent
	 *
	 * @return INextSearchPlatform
	 * @throws Exception
	 */
	public function getPlatform($silent = false) {
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
	 * @return INextSearchPlatform[]
	 * @throws Exception
	 */
	public function getPlatforms() {
		$this->loadPlatforms();

		$platforms = [];
		foreach ($this->platforms as $class) {
			try {
				$platform = \OC::$server->query((string)$class);
				if ($platform instanceof INextSearchPlatform) {
					$platforms[$class] = $platform;
				}
			} catch (QueryException $e) {
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
		$platform = \OC::$server->query((string)$selected);
		if (!($platform instanceof INextSearchPlatform)) {
			throw new PlatformIsNotCompatibleException(
				$selected . ' is not a compatible NextSearchPlatform'
			);
		}

		$platform->loadPlatform();
		$this->platform = $platform;
	}


	/**
	 * @return string
	 * @throws PlatformDoesNotExistException
	 * @throws PlatformNotSelectedException
	 */
	private function getSelectedPlatform() {
		$selected = $this->configService->getAppValue(ConfigService::SEARCH_PLATFORM);

		if ($selected === '') {
			throw new PlatformNotSelectedException('Admin have not selected any NextSearchPlatform');
		}

		if (!in_array($selected, $this->platforms)) {
			throw new PlatformDoesNotExistException(
				'NextSearchPlatform ' . $selected . ' is not available'
			);
		}

		return $selected;
	}

	/**
	 * @param string $appId
	 */
	private function loadPlatformsFromApp($appId) {
		$appInfo = OC_App::getAppInfo($appId);
		if (!is_array($appInfo) || !key_exists('fullnextsearch', $appInfo)
			|| !key_exists('platform', $appInfo['fullnextsearch'])) {
			return;
		}

		$platforms = $appInfo['fullnextsearch']['platform'];
		if (!is_array($platforms)) {
			$platforms = [$platforms];
		}

		$this->platforms = array_merge($this->platforms, $platforms);
	}


}