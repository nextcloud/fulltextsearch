<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Cron;

use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MigrationService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;

class Maintenance extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private MigrationService $migrationService,
		private ConfigService $configService,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(3600);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}


	/**
	 * @param mixed $argument
	 *
	 * @throws \OCP\DB\Exception
	 */
	protected function run($argument) {
		$size = $this->configService->getAppValue('size_migration_24');
		if ($size === '') {
			$size = 10000;
		}

		$this->migrationService->migrate24Chunk((int)$size);
	}

}
