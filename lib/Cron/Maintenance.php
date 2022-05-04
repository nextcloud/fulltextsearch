<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


namespace OCA\FullTextSearch\Cron;


use OC\BackgroundJob\TimedJob;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MigrationService;


class Maintenance extends TimedJob {


	/** @var MigrationService */
	private $migrationService;

	/** @var ConfigService */
	private $configService;


	/**
	 *
	 */
	public function __construct(MigrationService $migrationService, ConfigService $configService) {
		$this->setInterval(3600);

		$this->migrationService = $migrationService;
		$this->configService = $configService;
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
