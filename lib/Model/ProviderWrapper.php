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

