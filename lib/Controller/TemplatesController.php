<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Controller;

use Exception;
use OC\AppFramework\Http;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;


/**
 * Class TemplatesController
 *
 * @package OCA\FullTextSearch\Controller
 */
class TemplatesController extends Controller {
	public function __construct(
		IRequest $request,
		private ProviderService $providerService
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $providerId
	 *
	 * @return DataResponse
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function getOptionsPanel(string $providerId): DataResponse {
		$providerWrapper = $this->providerService->getProvider($providerId);
		$provider = $providerWrapper->getProvider();

		$searchTemplate = $provider->getSearchTemplate();

		$template = '';
		if ($searchTemplate->getTemplate() !== '') {
			$tmpl =
				new TemplateResponse(
					$providerWrapper->getAppId(), $searchTemplate->getTemplate(), [], 'blank'
				);
			$template = $tmpl->render();
		}

		$ret[$providerId] =
			[
				'options'  => $searchTemplate->getPanelOptions(),
				'template' => $template
			];

		return new DataResponse($ret, Http::STATUS_OK);
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return DataResponse
	 * @throws Exception
	 */
	public function getNavigationPanels(): DataResponse {
		$providers = $this->providerService->getProviders();

		$ret = [];
		foreach ($providers as $providerWrapper) {
			$provider = $providerWrapper->getProvider();
			$providerAppId = $providerWrapper->getAppId();

			$searchTemplate = $provider->getSearchTemplate();
			$ret[$providerAppId] =
				[
					'provider' => $provider->getId(),
					'title'    => $provider->getName(),
					'options'  => $searchTemplate->getNavigationOptions(),
					'css'      => $searchTemplate->getCss(),
					'icon'     => $searchTemplate->getIcon()
				];
		}

		return new DataResponse($ret, Http::STATUS_OK);
	}


}
