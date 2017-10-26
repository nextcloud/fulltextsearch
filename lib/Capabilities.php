<?php


namespace OCA\FullNextSearch;

use OCA\FullNextSearch\Service\ConfigService;
use OCA\FullNextSearch\Service\ProviderService;
use OCP\Capabilities\ICapability;


/**
 * Class Capabilities
 *
 * @package OCA\FullNextSearch
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
	 *    {"fullnextsearch":{"remote":true,"providers":[{"id":"files","name":"Files"}]}}
	 *
	 * if 'remote' is false, it means administrator does not allow search request with no CSRF check.
	 *
	 * 'providers' will returns the list of provider configured for this user.
	 * If a provider is not listed, no search will be available on it; so replace the 'files' search
	 * only if the 'files' provider is in the list
	 *
	 * @return array
	 */
	public function getCapabilities() {

		$providers = $this->providerService->getConfiguredProviders();

		return [
			'fullnextsearch' => [
				'remote'    => true,
				'providers' => $this->providerService->serialize($providers)
			]
		];
	}


}




