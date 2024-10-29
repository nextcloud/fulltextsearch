<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCP\FullTextSearch\IFullTextSearchPlatform;


/**
 * Class PlatformWrapper
 *
 * @package OCA\FullTextSearch\Model
 */
class PlatformWrapper {


	/** @var string */
	private $appId;

	/** @var string */
	private $class;

	/** @var IFullTextSearchPlatform */
	private $platform;

	/** @var string */
	private $version;


	/**
	 * Provider constructor.
	 *
	 * @param string $appId
	 * @param string $class
	 */
	public function __construct(string $appId, string $class) {
		$this->appId = $appId;
		$this->class = $class;
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
	 * @return PlatformWrapper
	 */
	public function setAppId(string $appId): PlatformWrapper {
		$this->appId = $appId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getClass(): string {
		return $this->class;
	}

	/**
	 * @param string $class
	 *
	 * @return PlatformWrapper
	 */
	public function setClass(string $class): PlatformWrapper {
		$this->class = $class;

		return $this;
	}


	/**
	 * @return IFullTextSearchPlatform
	 */
	public function getPlatform(): IFullTextSearchPlatform {
		return $this->platform;
	}

	/**
	 * @param IFullTextSearchPlatform $platform
	 *
	 * @return PlatformWrapper
	 */
	public function setPlatform(IFullTextSearchPlatform $platform): PlatformWrapper {
		$this->platform = $platform;

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
	 * @return PlatformWrapper
	 */
	public function setVersion(string $version): PlatformWrapper {
		$this->version = $version;

		return $this;
	}


}
