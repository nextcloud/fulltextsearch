<?php
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

namespace OCA\FullTextSearch\Controller;

use Exception;
use OC\AppFramework\Http;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

class TemplatesController extends Controller {

	/** @var IConfig */
	private $config;

	/** @var ConfigService */
	private $configService;

	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;


	/**
	 * TemplatesController constructor.
	 *
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param ConfigService $configService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, IConfig $config, ConfigService $configService,
		ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->config = $config;
		$this->configService = $configService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param $providerId
	 *
	 * @return DataResponse
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function getOptionsPanel($providerId) {
		$provider = $this->providerService->getProvider($providerId);

		$ret = [];
		$options = $provider->getOptionsTemplate();
		if (is_array($options) && array_key_exists('panel', $options)) {
			$tmpl = $this->getTemplate($provider, $options['panel']);
			$ret[$providerId] = $tmpl->render();
		}

		return new DataResponse($ret, Http::STATUS_OK);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 * @throws Exception
	 */
	public function getNavigationPanels() {
		$providers = $this->providerService->getProviders();

		$ret = [];
		foreach ($providers as $provider) {
			$providerId = $provider->getId();

			$options = $provider->getOptionsTemplate();
			$nav = [];
			if (is_array($options) && array_key_exists('navigation', $options)) {
				$nav = $options['navigation'];
			}

			if (array_key_exists('css', $nav)) {
				Util::addStyle($provider->getAppId(), $nav['css']);
			}

			$ret[$providerId] =
				[
					'title'      => $provider->getName(),
					'navigation' => $nav
				];
		}

		return new DataResponse($ret, Http::STATUS_OK);
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param array $panel
	 *
	 * @return TemplateResponse
	 * @throws Exception
	 */
	private function getTemplate(IFullTextSearchProvider $provider, $panel) {

		if (!is_array($panel) || !array_key_exists('template', $panel)) {
			throw new Exception('malformed option panel');
		}

		return new TemplateResponse($provider->getAppId(), $panel['template'], [], 'blank');
	}


}