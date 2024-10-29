<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;


use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\DatabaseException;
use OCA\FullTextSearch\Exceptions\ProviderOptionsDoesNotExistException;
use OCP\IConfig;
use OCP\PreConditionNotMetException;
use OCP\Util;


/**
 * Class ConfigService
 *
 * @package OCA\FullTextSearch\Service
 */
class ConfigService {


	const APP_NAVIGATION = 'app_navigation';
	const SEARCH_PLATFORM = 'search_platform';
	const PROVIDER_INDEXED = 'provider_indexed';
	const CRON_LAST_ERR_RESET = 'cron_err_reset';
	const TICK_TTL = 'tick_ttl';
	const COLLECTION_INDEXING_LIST = 'collection_indexing_list';
	const COLLECTION_INTERNAL = 'collection_internal';

	// Temp. can be removed after few major releases
	const MIGRATION_24 = 'migration_24';

	/** @var array */
	public $defaults = [
		self::SEARCH_PLATFORM => '',
		self::APP_NAVIGATION => '0',
		self::PROVIDER_INDEXED => '',
		self::CRON_LAST_ERR_RESET => '0',
		self::TICK_TTL => '1800',
		self::COLLECTION_INDEXING_LIST => 50,
		self::MIGRATION_24 => 1,
		self::COLLECTION_INTERNAL => 'local'
	];

	public function __construct(
		private IConfig $config,
		private ?string $userId
	) {
	}


	/**
	 * @return array
	 */
	public function getConfig(): array {
		$keys = array_keys($this->defaults);
		$data = [];

		foreach ($keys as $k) {
			$data[$k] = $this->getAppValue($k);
		}

		return $data;
	}


	/**
	 * @param array $save
	 */
	public function setConfig(array $save) {
		$keys = array_keys($this->defaults);

		foreach ($keys as $k) {
			if (array_key_exists($k, $save)) {
				$this->setAppValue($k, $save[$k]);
			}
		}
	}


	/**
	 * Get a version of an app
	 *
	 * @param string $appId
	 *
	 * @return string
	 */
	public function getAppVersion(string $appId): string {
		return $this->config->getAppValue($appId, 'installed_version', '');
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string {
		return $this->config->getSystemValueString(
			Application::APP_ID . '.' . $key,
			(string)$this->config->getAppValue(Application::APP_ID,
				$key,
				$this->defaults[$key] ?? '')
		);
	}

	/**
	 * @param string $config
	 *
	 * @return int
	 */
	public function getAppValueInt(string $config): int {
		return (int)$this->getAppValue($config);
	}


	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValue(string $key, string $value) {
		$this->config->setAppValue(Application::APP_ID, $key, $value);
	}

	/**
	 * remove a key
	 *
	 * @param string $key
	 */
	public function deleteAppValue(string $key) {
		$this->config->deleteAppValue(Application::APP_ID, $key);
	}

	/**
	 * Get a user value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getUserValue(string $key): string {
		$defaultValue = null;
		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getUserValue(
			$this->userId, Application::APP_ID, $key, $defaultValue
		);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @throws PreConditionNotMetException
	 */
	public function setUserValue(string $key, string $value) {
		$this->config->setUserValue($this->userId, Application::APP_ID, $key, $value);
	}

	/**
	 * Get a user value by key and user
	 *
	 * @param string $userId
	 * @param string $key
	 *
	 * @return string
	 */
	public function getValueForUser(string $userId, string $key) {
		return $this->config->getUserValue($userId, Application::APP_ID, $key);
	}

	/**
	 * Set a user value by key
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 *
	 * @throws PreConditionNotMetException
	 */
	public function setValueForUser(string $userId, string $key, string $value) {
		$this->config->setUserValue($userId, Application::APP_ID, $key, $value);
	}


	/**
	 * @param string $providerId
	 * @param string $options
	 * @param string $value
	 */
	public function setProviderOptions(string $providerId, string $options, string $value) {
		$arr = json_decode($this->getAppValue($options), true);
		if ($arr === null) {
			$arr = [];
		}

		$arr[$providerId] = $value;

		$this->setAppValue($options, json_encode($arr));
	}


	/**
	 * @param string $options
	 */
	public function resetProviderOptions(string $options) {
		$this->setAppValue($options, '');
	}


	/**
	 * @param string $providerId
	 * @param string $options
	 *
	 * @return string
	 * @throws ProviderOptionsDoesNotExistException
	 */
	public function getProviderOptions(string $providerId, string $options): string {
		$arr = json_decode($this->getAppValue($options), true);
		if ($arr === null) {
			$arr = [];
		}

		if (!key_exists($providerId, $arr)) {
			throw new ProviderOptionsDoesNotExistException();
		}

		return $arr[$providerId];
	}


	/**
	 * return the cloud version.
	 *
	 * @return int
	 */
	public function getCloudVersion(): int {
		$ver = Util::getVersion();

		return $ver[0];
	}


	/**
	 * @throws DatabaseException
	 */
	public function requireMigration24(): void {
		if ($this->getAppValueInt(self::MIGRATION_24) === 1) {
			return;
		}

		throw new DatabaseException('please run ./occ fulltextsearch:migration:24');
	}

	public function getInternalCollection(): string {
		return $this->getAppValue(self::COLLECTION_INTERNAL);
	}
}
