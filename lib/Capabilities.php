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
	 * @return array
	 */
	public function getCapabilities() {

		return [
			'fullnextsearch' => [
				'providers' => $this->providerService->getConfiguredProviderIds()
			]
		];
	}


}




