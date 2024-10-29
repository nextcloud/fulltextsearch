<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCP\FullTextSearch\IFullTextSearchProvider;


/**
 * Class ProviderWrapper
 *
 * @package OCA\FullTextSearch\Model
 */
class ProviderWrapper {


	/** @var string */
	private $appId;

	/** @var IFullTextSearchProvider */
	private $provider;

	/** @var string */
	private $version;


	/**
	 * Provider constructor.
	 *
	 * @param string $appId
	 * @param IFullTextSearchProvider $provider
	 */
	public function __construct(string $appId, IFullTextSearchProvider $provider) {
		$this->appId = $appId;
		$this->provider = $provider;
	}

	/**
	 * @return string
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @param string $appId
	 *
	 * @return ProviderWrapper
	 */
	public function setAppId(string $appId): ProviderWrapper {
		$this->appId = $appId;

		return $this;
	}


	/**
	 * @return IFullTextSearchProvider
	 */
	public function getProvider(): IFullTextSearchProvider {
		return $this->provider;
	}

	/**
	 * @param IFullTextSearchProvider $provider
	 *
	 * @return ProviderWrapper
	 */
	public function setProvider(IFullTextSearchProvider $provider): ProviderWrapper {
		$this->provider = $provider;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getVersion(): string {
		return $this->version;
	}

	/**
	 * @param string $version
	 *
	 * @return ProviderWrapper
	 */
	public function setVersion(string $version): ProviderWrapper {
		$this->version = $version;

		return $this;
	}


}

