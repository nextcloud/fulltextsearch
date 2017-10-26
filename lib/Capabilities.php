<?php


namespace OCA\FullNextSearch;

use OCA\FullNextSearch\Service\ConfigService;
use OCP\Capabilities\ICapability;


/**
 * Class Capabilities
 *
 * @package OCA\FullNextSearch
 */
class Capabilities implements ICapability {


	/** @var ConfigService */
	private $configService;


	/**
	 * Capabilities constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct(ConfigService $configService) {
		$this->configService = $configService;
	}


	/**
	 * Return this classes capabilities
	 *
	 * @return array
	 */
	public function getCapabilities() {
		return [
			'fullnextsearch' => [
				'configured' => (($this->configService->isConfigured()) ? '1' : '0'),
			]
		];
	}
}




