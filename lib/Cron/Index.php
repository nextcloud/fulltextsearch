<?php

/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\FullNextSearch\Cron;

use Exception;
use OC\BackgroundJob\TimedJob;
use OCA\FullNextSearch\AppInfo\Application;
use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\Runner;
use OCA\FullNextSearch\Service\IndexService;
use OCA\FullNextSearch\Service\MiscService;
use OCA\FullNextSearch\Service\PlatformService;
use OCA\FullNextSearch\Service\ProviderService;
use OCA\FullNextSearch\Service\RunningService;
use OCP\IUserManager;

class Index extends TimedJob {

	/** @var IUserManager */
	private $userManager;

	/** @var IndexService */
	private $indexService;

	/** @var PlatformService */
	private $platformService;

	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;

	/** @var Runner */
	private $runner;

	public function __construct() {
		$this->setInterval(5); // 20 minutes
	}


	protected function run($argument) {
		$app = new Application();
		$c = $app->getContainer();

		$this->userManager = $c->query(IUserManager::class);
		$runningService = $c->query(RunningService::class);
		$this->runner = new Runner($runningService, 'cronIndex');

		$this->indexService = $c->query(IndexService::class);
		$this->platformService = $c->query(PlatformService::class);
		$this->providerService = $c->query(ProviderService::class);
		$this->miscService = $c->query(MiscService::class);


		try {
			$this->runner->start();
		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			exit(1);
		}

		$this->liveCycle();
		$this->runner->stop();
	}


	private function liveCycle() {

		$platform = $this->platformService->getPlatform();
		$indexes = $this->indexService->getQueuedIndexes();

		foreach ($indexes as $index) {
			$this->runner->update('indexing');

			try {
				$provider = $this->providerService->getProvider($index->getProviderId());
				$this->indexService->updateDocument($platform, $provider, $index);
			} catch (Exception $e) {
				$this->runner->exception($e->getMessage(), false);
				// TODO - upgrade error number - after too many errors, delete index
				// TODO - do not count error if elasticsearch is down.
			}
		}


	}
}
