<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use Exception;
use OCA\FullTextSearch\ConfigLexicon;

class SettingsService {
	public function __construct(
		private PlatformService $platformService,
		private ProviderService $providerService,
	) {
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 */
	public function checkConfig(array &$data): bool {
		// convert to bool
		if (is_string($data[ConfigLexicon::APP_NAVIGATION])) {
			$data[ConfigLexicon::APP_NAVIGATION] = in_array($data[ConfigLexicon::APP_NAVIGATION], ['1', 'yes', 'on', 'true']);
		}

		return true;
	}


	/**
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function completeSettings(array &$data) {
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
	private function completeSettingsPlatforms(): array {
		$list = [];
		$platforms = $this->platformService->getPlatforms();
		foreach ($platforms as $wrapper) {
			$platform = $wrapper->getPlatform();
			$list[$wrapper->getClass()] = [
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
	private function completeSettingsProviders(): array {
		$list = [];
		$providers = $this->providerService->getProviders();
		foreach ($providers as $providerWrapper) {
			$provider = $providerWrapper->getProvider();

			$list[$provider->getId()] = $provider->getName();
		}

		return $list;
	}

}
