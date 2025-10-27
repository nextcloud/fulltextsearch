<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\RepairStep;

use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\ConfigLexicon;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AppEnabled implements IRepairStep {
	public function __construct(
		private readonly IAppConfig $appConfig,
	) {
	}

	public function getName() {
		return 'prep re-sync';
	}

	public function run(IOutput $output) {
		$this->appConfig->setValueInt(Application::APP_ID, ConfigLexicon::ENABLED_SINCE, time());
	}
}
