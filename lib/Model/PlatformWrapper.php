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
