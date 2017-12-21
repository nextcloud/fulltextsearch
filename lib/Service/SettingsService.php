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
use OCP\IConfig;

class SettingsService {

	/** @var IConfig */
	private $config;

	/** @var string */
	private $userId;

	/** @var PlatformService */
	private $platformService;

	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;

	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig $config
	 * @param string $userId
	 * @param PlatformService $platformService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IConfig $config, $userId, PlatformService $platformService, ProviderService $providerService,
		MiscService $miscService
	) {
		$this->config = $config;
		$this->userId = $userId;
		$this->platformService = $platformService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public function checkConfig($data) {
		if (!is_numeric($data['index_chunk'])) {
			return false;
		}

		return true;
	}


	/**
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function completeSettings(&$data) {
		$data = array_merge(
			$data, [
					 'platforms_all' => $this->completeSettingsPlatforms(),
					 'providers_all' => $this->completeSettingsProviders()
				 ]
		);

	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function completeSettingsPlatforms() {
		$list = [];
		$platforms = $this->platformService->getPlatforms();
		$classes = array_keys($platforms);
		foreach ($classes as $class) {
			$platform = $platforms[$class];
			$list[$class] = [
				'id'   => $platform->getId(),
				'name' => $platform->getName()
			];
		}

		return $list;
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function completeSettingsProviders() {
		$list = [];
		$providers = $this->providerService->getProviders();
		foreach ($providers as $provider) {
			$list[$provider->getId()] = $provider->getName();
		}

		return $list;
	}

}
