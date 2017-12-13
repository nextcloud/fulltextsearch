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

namespace OCA\FullNextSearch\Command;

use Exception;
use OCA\FullNextSearch\Exceptions\InterruptException;
use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\ExtendedBase;
use OCA\FullNextSearch\Model\Runner;
use OCA\FullNextSearch\Service\IndexService;
use OCA\FullNextSearch\Service\MiscService;
use OCA\FullNextSearch\Service\PlatformService;
use OCA\FullNextSearch\Service\ProviderService;
use OCA\FullNextSearch\Service\RunningService;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Live extends ExtendedBase {

	const CYCLE_DELAY = 10;


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

	/**
	 * Index constructor.
	 *
	 * @param IUserManager $userManager
	 * @param RunningService $runningService
	 * @param IndexService $indexService
	 * @param PlatformService $platformService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IUserManager $userManager, RunningService $runningService, IndexService $indexService,
		PlatformService $platformService, ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();
		$this->userManager = $userManager;

		$this->runner = new Runner($runningService, 'commandLive');
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
		$this->setName('fullnextsearch:live')
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


	private function liveCycle() {

		$platform = $this->platformService->getPlatform();
		$platform->setRunner($this->runner);

		while (true) {

			$indexes = $this->indexService->getQueuedIndexes();

			foreach ($indexes as $index) {
				$this->runner->update('indexing');

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

			$this->runner->update('waiting');

			sleep(self::CYCLE_DELAY);
		}

		$this->runner->stop();

	}


}



