<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Settings;


use Exception;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;

/**
 * Class Admin
 *
 * @package OCA\FullTextSearch\Settings
 */
class Admin implements ISettings {
	public function __construct(
	) {
	}


	/**
	 * @return TemplateResponse
	 * @throws Exception
	 */
	public function getForm(): TemplateResponse {
		return new TemplateResponse(Application::APP_ID, 'settings.admin', []);
	}


	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): string {
		return Application::APP_ID;
	}


	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	public function getPriority(): int {
		return 0;
	}


}
