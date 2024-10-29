<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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




