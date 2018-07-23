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
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\ExtendedBase;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Index extends ExtendedBase {

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

		$this->runner = new Runner($runningService, 'commandIndex');
		$this->indexService = $indexService;
		$this->indexService->setRunner($this->runner);

		$this->platformService = $platformService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:index')
			 ->setDescription('Index files')
			 ->addArgument('options', InputArgument::OPTIONAL, 'options');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$options = $this->generateIndexOptions($input);

		try {
			$this->runner->sourceIsCommandLine($this, $output);
			$this->runner->start();

			$providers = $this->providerService->getProviders();
			foreach ($providers as $provider) {

				if (!$this->isIncludedProvider($options, $provider->getId())) {
					continue;
				}

				$this->runner->output('indexing ' . $provider->getName() . '.');
				$provider->setRunner($this->runner);
				$provider->setIndexOptions($options);
				$this->indexProvider($provider, $options);
			}

		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

		$this->runner->stop();
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IndexOptions $options
	 *
	 * @throws Exception
	 */
	private function indexProvider(IFullTextSearchProvider $provider, IndexOptions $options) {
		$platform = $this->platformService->getPlatform();
		$platform->initializeIndex();
		$provider->onInitializingIndex($platform);

		$platform->setRunner($this->runner);

		$users = $this->generateUserList($options);
		foreach ($users as $user) {
			if ($user === null) {
				continue;
			}

			$this->runner->output(' USER: ' . $user->getUID());
			$this->indexService->indexProviderContentFromUser(
				$platform, $provider, $user->getUID(), $options
			);
		}

		$this->providerService->setProviderAsIndexed($provider, true);

	}


	/**
	 * @param InputInterface $input
	 *
	 * @return IndexOptions
	 */
	private function generateIndexOptions(InputInterface $input) {
		$jsonOptions = $input->getArgument('options');
		$options = json_decode($jsonOptions, true);

		if (!is_array($options)) {
			$options = [];
		}

		return new IndexOptions($options);
	}


	/**
	 * @param IndexOptions $options
	 * @param string $providerId
	 *
	 * @return bool
	 */
	private function isIncludedProvider(IndexOptions $options, $providerId) {
		if ($options->getOption('provider', '') !== ''
			&& $options->getOption('provider') !== $providerId) {
			return false;
		}

		if ($options->getOption('providers', null) !== null
			&& is_array($options->getOption('providers'))) {
			return (in_array($providerId, $options->getOption('providers')));
		}

		return true;
	}


	/**
	 * @param IndexOptions $options
	 *
	 * @return array
	 */
	private function generateUserList(IndexOptions $options) {
		if ($options->getOption('user', '') !== '') {
			return [$this->userManager->get($options->getOption('user'))];
		}

		if ($options->getOption('users', null) !== null
			&& is_array($options->getOption('users'))) {
			return array_map([$this->userManager, 'get'], $options->getOption('users'));
		}

		return $this->userManager->search('');
	}
}



