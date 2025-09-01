<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FullTextSearch\Controller;

use Exception;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private ConfigService $configService,
		private SettingsService $settingsService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	public function getSettingsAdmin(): DataResponse {
		$data = $this->configService->getConfig();
		$this->settingsService->completeSettings($data);

		return new DataResponse($data, Http::STATUS_OK);
	}

	public function setSettingsAdmin(array $data): DataResponse {
		if ($this->settingsService->checkConfig($data)) {
			$this->configService->setConfig($data);
		}

		return $this->getSettingsAdmin();
	}

}
