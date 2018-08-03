<?php
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

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Exceptions\InterruptException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\ExtendedBase;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Live extends ExtendedBase {

	const CYCLE_DELAY = 10;


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

	/**
	 * Index constructor.
	 *
	 * @param IUserManager $userManager
	 * @param RunningService $runningService
	 * @param ConfigService $configService
	 * @param IndexService $indexService
	 * @param PlatformService $platformService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IUserManager $userManager, RunningService $runningService, ConfigService $configService,
		IndexService $indexService, PlatformService $platformService,
		ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();
		$this->userManager = $userManager;

		$this->runner = new Runner($runningService, 'commandLive');
		$this->configService = $configService;
		$this->indexService = $indexService;
		$this->platformService = $platformService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:live')
			 ->setDescription('Index files');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		if ($this->configService->getCloudVersion() < 14) {
			throw new Exception('This feature is only available on Nextcloud 14 or newer');
		}

		try {
			$this->runner->sourceIsCommandLine($this, $output);
			$this->runner->start();
			$this->runner->output('live.');

		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

		$this->indexService->setRunner($this->runner);

		$this->liveCycle();
		$this->runner->stop();
	}


	/**
	 * @throws Exception
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	private function liveCycle() {

		$platform = $this->platformService->getPlatform();
		$platform->setRunner($this->runner);

		while (true) {

			$indexes = $this->indexService->getQueuedIndexes();

			foreach ($indexes as $index) {
				$this->runner->updateAction('indexing');

				try {
					$provider = $this->providerService->getProvider($index->getProviderId());
					$provider->setRunner($this->runner);
					$this->indexService->updateDocument($platform, $provider, $index);
				} catch (Exception $e) {
					$this->runner->exception($e->getMessage(), false);
					// TODO - upgrade error number - after too many errors, delete index
					// TODO - do not count error if elasticsearch is down.
				}
			}

			$this->runner->updateAction('waiting');

			sleep(self::CYCLE_DELAY);
		}

		$this->runner->stop();

	}


}



