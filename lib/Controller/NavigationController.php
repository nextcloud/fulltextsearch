<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Controller;

use OCA\FullTextSearch\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

class NavigationController extends Controller {
	public function __construct(
		IRequest $request,
		private IConfig $config,
		private IInitialState $initialStateService,
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
		$this->initialStateService->provideInitialState('themingName', $themingName);

		Util::addScript(Application::APP_ID, 'fulltextsearch-navigate');
		Util::addStyle(Application::APP_ID, 'fulltextsearch-navigate');

		return new TemplateResponse(Application::APP_ID, 'navigate');
	}

}
