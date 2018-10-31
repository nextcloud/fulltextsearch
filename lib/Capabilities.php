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


namespace OCA\FullTextSearch;


use Exception;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\Capabilities\ICapability;


/**
 * Class Capabilities
 *
 * @package OCA\FullTextSearch
 */
class Capabilities implements ICapability {


	/** @var ProviderService */
	private $providerService;


	/**
	 * Capabilities constructor.
	 *
	 * @param ProviderService $providerService
	 */
	public function __construct(ProviderService $providerService) {
		$this->providerService = $providerService;
	}


	/**
	 * Return this classes capabilities
	 *
	 * Result to be expected:
	 *    {"fulltextsearch":{"remote":true,"providers":[{"id":"files","name":"Files"}]}}
	 *
	 * if 'remote' is false, it means administrator does not allow search request with no CSRF
	 * check.
	 *
	 * 'providers' will returns the list of provider configured for this user.
	 * If a provider is not listed, no search will be available on it; so replace the 'files'
	 * search
	 * only if the 'files' provider is in the list
	 *
	 * @return array<string,array<string,boolean|array>>
	 * @throws Exception
	 */
	public function getCapabilities(): array {

		$providers = $this->providerService->getConfiguredProviders();

		return [
			'fulltextsearch' => [
				'remote'    => true,
				'providers' => $this->providerService->serialize($providers)
			]
		];
	}


}




