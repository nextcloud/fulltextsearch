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
use OCA\FullTextSearch\Service\CliService;
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


	const PANEL_INDEX = 'indexing';
	const PANEL_INDEX_LINE_HEADER = '┌─ Indexing ────';
	const PANEL_INDEX_LINE_ACCOUNT = '│ Account: %userId%';
	const PANEL_INDEX_LINE_DOCUMENT = "│ Indexing <info>provider</info>/<info>document</info>";
	const PANEL_INDEX_LINE_TITLE = '│ Title: %title%';
	const PANEL_INDEX_LINE_CONTENT = '│ Content: empty';
	const PANEL_INDEX_LINE_OPTIONS = '│ Options: []';
	const PANEL_INDEX_LINE_RESULT = '│ Result: %result%';
	const PANEL_INDEX_LINE_FOOTER = '└──';

	const PANEL_STATUS = 'status';
	const PANEL_STATUS_LINE_HEADER = '┌─ Status ────';
	const PANEL_STATUS_LINE_DOCUMENTS_TOTAL = '│ Total document for this user:';
	const PANEL_STATUS_LINE_DOCUMENTS_LEFT = '│ Document left:';
	const PANEL_STATUS_LINE_ERRORS = '│ Errors:';
	const PANEL_STATUS_LINE_FOOTER = '└──';

	const PANEL_LINE_EMPTY = '│ ';

	const PANEL_COMMANDS_ROOT = 'root';
	const PANEL_COMMANDS_ROOT_LINE = '## q:quit ## p:pause';
	const PANEL_COMMANDS_PAUSED = 'paused';
	const PANEL_COMMANDS_PAUSED_LINE = '## q:quit ## u:unpause ## n:next';


	/** @var IUserManager */
	private $userManager;

	/** @var RunningService */
	private $runningService;

	/** @var CliService */
	private $cliService;

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
	 * @param CliService $cliService
	 * @param IndexService $indexService
	 * @param PlatformService $platformService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IUserManager $userManager, RunningService $runningService, CliService $cliService,
		IndexService $indexService, PlatformService $platformService,
		ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();
		$this->userManager = $userManager;

		$this->runningService = $runningService;
		$this->cliService = $cliService;
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
		$this->setName('fulltextsearch:index')
			 ->setDescription('Index files')
			 ->addArgument('options', InputArgument::OPTIONAL, 'options');
	}


	/**
	 * @throws Exception
	 */
	public function interrupted() {
		if ($this->hasBeenInterrupted()) {
			throw new \Exception('ctrl-c');
		}
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		readline_callback_handler_install(
			'', function() {
		}
		);
		stream_set_blocking(STDIN, false);

		$options = $this->generateIndexOptions($input);

		$this->runner = new Runner($this->runningService, 'commandIndex');
		$this->runner->onKeyPress([$this, 'onKeyPressed']);
		$this->indexService->setRunner($this->runner);
		$this->cliService->setRunner($this->runner);

		$this->cliService->createPanel(
			self::PANEL_INDEX, [
								 self::PANEL_INDEX_LINE_HEADER,
								 self::PANEL_INDEX_LINE_ACCOUNT,
								 self::PANEL_INDEX_LINE_DOCUMENT,
								 self::PANEL_INDEX_LINE_TITLE,
								 self::PANEL_INDEX_LINE_CONTENT,
								 self::PANEL_INDEX_LINE_OPTIONS,
								 self::PANEL_INDEX_LINE_RESULT,
								 self::PANEL_INDEX_LINE_FOOTER,

							 ]
		);

		$this->cliService->createPanel(
			self::PANEL_STATUS, [
								  self::PANEL_STATUS_LINE_HEADER,
								  self::PANEL_STATUS_LINE_DOCUMENTS_TOTAL,
								  self::PANEL_STATUS_LINE_DOCUMENTS_LEFT,
								  self::PANEL_STATUS_LINE_ERRORS,
								  self::PANEL_LINE_EMPTY,
								  self::PANEL_LINE_EMPTY,
								  self::PANEL_LINE_EMPTY,
								  self::PANEL_STATUS_LINE_FOOTER,
							  ]
		);

		$this->cliService->createPanel(
			self::PANEL_COMMANDS_ROOT, [
										 self::PANEL_COMMANDS_ROOT_LINE
									 ]
		);

		$this->cliService->createPanel(
			self::PANEL_COMMANDS_PAUSED, [
										   self::PANEL_COMMANDS_PAUSED_LINE
									   ]
		);


		$this->cliService->initDisplay();
		$this->cliService->displayPanel('topPanel', self::PANEL_INDEX);
		$this->cliService->displayPanel('bottomPanel', self::PANEL_STATUS);
		$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_ROOT);
		$this->cliService->runDisplay($output);

//		while (1) {
//			$this->runner->update();
//			sleep(1);
//		}

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
	 * @param $key
	 */
	public function onKeyPressed($key) {
		$key = strtolower($key);
		if ($key === 'q') {
			exit();
		}

		$current = $this->cliService->currentPanel('commands');
		if ($current === self::PANEL_COMMANDS_ROOT) {
			if ($key === 'p') {
				$this->cliService->switchPanel('commands', self::PANEL_COMMANDS_PAUSED);
			}
		}
		if ($current === self::PANEL_COMMANDS_PAUSED) {
			if ($key === 'u') {
				$this->cliService->switchPanel('commands', self::PANEL_COMMANDS_ROOT);
			}
		}
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



