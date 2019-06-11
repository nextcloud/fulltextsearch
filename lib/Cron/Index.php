<?php
declare(strict_types=1);


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


namespace OCA\FullTextSearch\Cron;


use Exception;
use OC\BackgroundJob\TimedJob;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\AppFramework\QueryException;
use OCP\IUserManager;


class Index extends TimedJob {

	const HOUR_ERR_RESET = 240;


	/** @var IUserManager */
	private $userManager;

	/** @var ConfigService */
	private $configService;

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
		$this->setInterval(12 * 60); // 12 minutes
	}


	/**
	 * @param mixed $argument
	 *
	 * @throws QueryException
	 */
	protected function run($argument) {
		$app = new Application();
		$c = $app->getContainer();

		$this->userManager = $c->query(IUserManager::class);
		$runningService = $c->query(RunningService::class);
		$this->runner = new Runner($runningService, 'cronIndex');

		$this->configService = $c->query(ConfigService::class);
		$this->indexService = $c->query(IndexService::class);
		$this->platformService = $c->query(PlatformService::class);
		$this->providerService = $c->query(ProviderService::class);
		$this->miscService = $c->query(MiscService::class);

		try {
			$this->runner->start();
			$this->liveCycle();
			$this->runner->stop();
		} catch (RunnerAlreadyUpException $e) {
		} catch (Exception $e) {
			$this->miscService->log('Exception while cronIndex: ' . get_class($e) . ' - ' . $e->getMessage());
			$this->runner->exception($e->getMessage(), true);
		}

	}


	/**
	 * @throws Exception
	 */
	private function liveCycle() {

		$wrapper = $this->platformService->getPlatform(true);
		$platform = $wrapper->getPlatform();

		$all = $this->shouldWeGetAllIndex();
		$indexes = $this->indexService->getQueuedIndexes($all);

		foreach ($indexes as $index) {
			$this->runner->updateAction('indexing');

			try {
				$providerWrapper = $this->providerService->getProvider($index->getProviderId());
				$provider = $providerWrapper->getProvider();
				if (!$this->providerService->isProviderIndexed($provider->getId())) {
					continue;
				}

				$this->indexService->updateDocument($platform, $provider, $index);
			} catch (Exception $e) {
				$this->runner->exception($e->getMessage(), false);
				// TODO - upgrade error number - after too many errors, delete index
				// TODO - do not count error if elasticsearch is down.
			}
		}

	}


	/**
	 * @return bool
	 */
	private function shouldWeGetAllIndex(): bool {
		$lastErrReset = (int)$this->configService->getAppValue(ConfigService::CRON_LAST_ERR_RESET);

		if ($lastErrReset === 0) {
			$this->setLastErrReset();

			return false;
		}

		if ($lastErrReset < (time() - (self::HOUR_ERR_RESET * 3600))) {
			$this->setLastErrReset();

			return true;
		}

		return false;
	}

	/**
	 *
	 */
	private function setLastErrReset() {
		$this->configService->setAppValue(ConfigService::CRON_LAST_ERR_RESET, (string) time());
	}
}
