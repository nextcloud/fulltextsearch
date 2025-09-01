<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FullTextSearch\Service;

use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\ConfigLexicon;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;

class ConfigService {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
	) {
	}

	public function getConfig(): array {
		return [
			ConfigLexicon::APP_NAVIGATION => $this->appConfig->getAppValueBool(ConfigLexicon::APP_NAVIGATION),
			ConfigLexicon::SEARCH_PLATFORM => $this->appConfig->getAppValueString(ConfigLexicon::SEARCH_PLATFORM),
			ConfigLexicon::COLLECTION_INTERNAL => $this->appConfig->getAppValueString(ConfigLexicon::COLLECTION_INTERNAL),
			ConfigLexicon::CRON_LAST_ERR_RESET => $this->appConfig->getAppValueInt(ConfigLexicon::CRON_LAST_ERR_RESET),
			ConfigLexicon::TICK_TTL => $this->appConfig->getAppValueInt(ConfigLexicon::TICK_TTL),
			ConfigLexicon::COLLECTION_INDEXING_LIST => $this->appConfig->getAppValueInt(ConfigLexicon::COLLECTION_INDEXING_LIST),
			ConfigLexicon::COLLECTION_LINKS => $this->appConfig->getAppValueArray(ConfigLexicon::COLLECTION_LINKS),
		];
	}

	public function setConfig(array $save): void {
		foreach(array_keys($save) as $k) {
			switch($k) {
				case ConfigLexicon::APP_NAVIGATION:
					$this->appConfig->setAppValueBool($k, $save[$k]);
					break;

				case ConfigLexicon::SEARCH_PLATFORM:
				case ConfigLexicon::COLLECTION_INTERNAL:
					$this->appConfig->setAppValueString($k, $save[$k]);
					break;

				case ConfigLexicon::CRON_LAST_ERR_RESET:
				case ConfigLexicon::TICK_TTL:
				case ConfigLexicon::COLLECTION_INDEXING_LIST:
					$this->appConfig->setAppValueInt($k, $save[$k]);
					break;

				case ConfigLexicon::COLLECTION_LINKS:
					$this->appConfig->setAppValueArray($k, $save[$k]);
					break;
			}
		}
	}

	public function getAppValue(string $key): string {
		return $this->config->getSystemValueString(Application::APP_ID . '.' . $key,
			(string)$this->appConfig->getAppValueString($key)
		);
	}

	public function getInternalCollection(): string {
		return $this->appConfig->getAppValueString(ConfigLexicon::COLLECTION_INTERNAL);
	}
}
