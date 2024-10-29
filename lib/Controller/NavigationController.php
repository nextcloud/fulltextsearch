<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Controller;

use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\FullTextSearch\IFullTextSearchManager;
use OCP\IConfig;
use OCP\IRequest;

class NavigationController extends Controller {
	public function __construct(
		IRequest $request,
		private IConfig $config,
		private IFullTextSearchManager $fullTextSearchManager,
	) {
		parent::__construct(Application::APP_ID, $request);
	}


	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @return TemplateResponse
	 */
	public function navigate(): TemplateResponse {
		$themingName = $this->config->getAppValue('theming', 'name', 'Nextcloud');
		$data = ['themingName' => $themingName];

		$this->fullTextSearchManager->addJavascriptAPI();

		return new TemplateResponse(Application::APP_ID, 'navigate', $data);
	}

}

