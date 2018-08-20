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
use OCA\FullTextSearch\Model\Index as ModelIndex;
use OCA\FullTextSearch\Service\CliService;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\IUserManager;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;


class Live extends ExtendedBase {

	const CYCLE_DELAY = 10;

	const PANEL_RUN = 'run';
	const PANEL_RUN_LINE_MEMORY = 'Memory: %_memory%';

	const PANEL_INDEX = 'indexing';
	const PANEL_INDEX_LINE_HEADER = '┌─ Indexing %_paused% ────';
	const PANEL_INDEX_LINE_ACCOUNT = '│ Provider: <info>%providerName:-20s%</info> Account: <info>%userId%</info>';
	const PANEL_INDEX_LINE_ACTION = '│ Action: <info>%action%</info>';
	const PANEL_INDEX_LINE_DOCUMENT = '│ Document: <info>%documentId%</info>';
	const PANEL_INDEX_LINE_INFO = '│ Info: <info>%info%</info>';
	const PANEL_INDEX_LINE_TITLE = '│ Title: <info>%title%</info>';
	const PANEL_INDEX_LINE_CONTENT = '│ Content size: <info>%content%</info>';
	const PANEL_INDEX_LINE_RESULT = '│ Result: %resultColored%';
	const PANEL_INDEX_LINE_FOOTER = '└──';

	const PANEL_STATUS = 'status';
	const PANEL_STATUS_LINE_HEADER = '┌─ Status ────';
	const PANEL_STATUS_LINE_DOCUMENTS = '│ Progress: %documentLeft:6s%/%documentTotal%   %progressStatus%';
//	const PANEL_STATUS_LINE_DOCUMENTS_LEFT = '│ Document left:';
	const PANEL_STATUS_LINE_ERRORS = '│ Error: <comment>%errorCurrent:6s%</comment>/<comment>%errorTotal%</comment>';
	const PANEL_STATUS_LINE_ERROR_EXCEPTION = '│ Exception: <comment>%errorException%</comment>';
	const PANEL_STATUS_LINE_ERROR_MESSAGE1 = '│ Message: <comment>%errorMessageA%</comment>';
	const PANEL_STATUS_LINE_ERROR_MESSAGE2 = '│ <comment>%errorMessageB%</comment>';
	const PANEL_STATUS_LINE_ERROR_MESSAGE3 = '│ <comment>%errorMessageC%</comment>';
	const PANEL_STATUS_LINE_ERROR_INDEX = '│ Index: <comment>%errorIndex%</comment>';


	const PANEL_STATUS_LINE_FOOTER = '└──';

	const PANEL_LINE_EMPTY = '│ ';

	const PANEL_COMMANDS_ROOT = 'root';
	const PANEL_COMMANDS_ROOT_LINE = '## <char>q</char>:quit ## <char>p</char>:pause ';
	const PANEL_COMMANDS_PAUSED = 'paused';
	const PANEL_COMMANDS_PAUSED_LINE = '## <char>q</char>:quit ## <char>u</char>:unpause ## <char>n</char>:next step';
	const PANEL_COMMANDS_DONE = 'done';
	const PANEL_COMMANDS_DONE_LINE = '## <char>q</char>:quit';
	const PANEL_COMMANDS_ERRORS = 'errors';
	const PANEL_COMMANDS_ERRORS_LINE = '## <char>f</char>:first error ## <char>h</char>/<char>j</char>:prec/next error ## <char>d</char>:delete error ## <char>l</char>:last error';


	/** @var IUserManager */
	private $userManager;

	/** @var ConfigService */
	private $configService;

	/** @var CliService */
	private $cliService;

	/** @var RunningService */
	private $runningService;

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

	/** @var Terminal */
	private $terminal;

	/** @var array */
	private $errors = [];

	/** @var bool */
	private $navigateLastError = true;


	/**
	 * Live constructor.
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
		CliService $cliService, IndexService $indexService, PlatformService $platformService,
		ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();
		$this->userManager = $userManager;

		$this->runner = new Runner($runningService, 'commandLive');
		$this->configService = $configService;
		$this->cliService = $cliService;
		$this->runningService = $runningService;
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

		/** do not get stuck while waiting interactive input */
		readline_callback_handler_install(
			'', function() {
		}
		);
		stream_set_blocking(STDIN, false);

		$this->terminal = new Terminal();

		$outputStyle = new OutputFormatterStyle('white', 'black', ['bold']);
		$output->getFormatter()
			   ->setStyle('char', $outputStyle);

		$this->runner = new Runner($this->runningService, 'commandIndex', ['nextStep' => 'n']);
		$this->runner->onKeyPress([$this, 'onKeyPressed']);
		$this->runner->onNewAction([$this, 'onNewAction']);
		$this->runner->onNewIndexError([$this, 'onNewIndexError']);

		$this->indexService->setRunner($this->runner);
		$this->cliService->setRunner($this->runner);

		$this->generatePanels();


		try {
			$this->runner->sourceIsCommandLine($this, $output);
			$this->runner->start();

			$this->cliService->runDisplay($output);
			$this->generateIndexErrors();
			$this->displayError();

			$this->liveCycle();

		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

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

			$this->runner->updateAction('waiting', true);

			usleep(300000);
		}

		$this->runner->stop();

	}


	/**
	 * @param $key
	 */
	public function onKeyPressed($key) {
		$key = strtolower($key);
		if ($key === 'q') {
			try {
				$this->runner->stop();
			} catch (TickDoesNotExistException $e) {
				/** we do nohtin' */
			}
			exit();
		}

		$current = $this->cliService->currentPanel('commands');
		if ($current === self::PANEL_COMMANDS_ROOT && $key === 'p') {
			$this->cliService->switchPanel('commands', self::PANEL_COMMANDS_PAUSED);
			$this->runner->pause(true);
		}
		if ($current === self::PANEL_COMMANDS_PAUSED && $key === 'u') {
			$this->cliService->switchPanel('commands', self::PANEL_COMMANDS_ROOT);
			$this->runner->pause(false);
		}

		if ($key === 'f') {
			$this->displayError(-99);
		}
		if ($key === 'h') {
			$this->displayError(-1);
		}
		if ($key === 'j') {
			$this->displayError(1);
		}
		if ($key === 'l') {
			$this->displayError(99);
		}
		if ($key === 'd') {
			$this->deleteError();
		}
	}


	/**
	 * @param array $error
	 */
	public function onNewIndexError($error) {
		$this->errors[] = $error;
		$this->displayError();
	}


	/**
	 * @param string $action
	 */
	public function onNewAction($action) {

		if ($action === 'indexChunk' || $action === 'indexChunkEnd') {
			$this->runner->setInfoArray(
				[
					'documentId' => '',
					'title'      => '',
					'content'    => ''
				]
			);
		}
	}


	/**
	 *
	 */
	private function generatePanels() {

		$this->cliService->createPanel(
			self::PANEL_RUN,
			[
				self::PANEL_RUN_LINE_MEMORY
			]
		);
		$this->cliService->createPanel(
			self::PANEL_INDEX, [
								 self::PANEL_INDEX_LINE_HEADER,
								 self::PANEL_INDEX_LINE_ACCOUNT,
								 self::PANEL_INDEX_LINE_ACTION,
								 self::PANEL_INDEX_LINE_DOCUMENT,
								 self::PANEL_INDEX_LINE_INFO,
								 self::PANEL_INDEX_LINE_TITLE,
								 self::PANEL_INDEX_LINE_CONTENT,
								 self::PANEL_INDEX_LINE_RESULT,
								 self::PANEL_INDEX_LINE_FOOTER,
							 ]
		);

		$this->cliService->createPanel(
			self::PANEL_STATUS, [
								  self::PANEL_STATUS_LINE_HEADER,
								  self::PANEL_STATUS_LINE_DOCUMENTS,
								  self::PANEL_STATUS_LINE_ERRORS,
								  self::PANEL_STATUS_LINE_ERROR_EXCEPTION,
								  self::PANEL_STATUS_LINE_ERROR_MESSAGE1,
								  self::PANEL_STATUS_LINE_ERROR_MESSAGE2,
								  self::PANEL_STATUS_LINE_ERROR_MESSAGE3,
								  self::PANEL_STATUS_LINE_ERROR_INDEX,
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

		$this->cliService->createPanel(
			self::PANEL_COMMANDS_ERRORS, [
										   self::PANEL_COMMANDS_ERRORS_LINE
									   ]
		);

		$this->cliService->initDisplay();
		$this->cliService->displayPanel('run', self::PANEL_RUN);
		$this->cliService->displayPanel('topPanel', self::PANEL_INDEX);
		$this->cliService->displayPanel('bottomPanel', self::PANEL_STATUS);

		$this->cliService->displayPanel('errors', self::PANEL_COMMANDS_ERRORS);

		if ($this->runner->isPaused()) {
			$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_PAUSED);
		} else {
			$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_ROOT);
		}

		$this->runner->setInfoArray(
			[
				'userId'         => '',
				'providerName'   => '',
				'_memory'        => '',
				'documentId'     => '',
				'action'         => '',
				'info'           => '',
				'title'          => '',
				'_paused'        => '',
				'content'        => '',
				'resultColored'  => '',
				'documentLeft'   => '',
				'documentTotal'  => '',
				'progressStatus' => '',
				'errorCurrent'   => '0',
				'errorTotal'     => '0',
				'errorMessageA'  => '',
				'errorMessageB'  => '',
				'errorMessageC'  => '',
				'errorException' => '',
				'errorIndex'     => ''
			]
		);
	}


	/**
	 *
	 */
	private function generateIndexErrors() {
		$indexes = $this->indexService->getErrorIndexes();

		foreach ($indexes as $index) {
			foreach ($index->getErrors() as $error) {
				$this->errors[] = [
					'index'     => $index,
					'message'   => $error['message'],
					'exception' => $error['exception'],
					'severity'  => $error['sev']
				];
			}

		}


	}


	/**
	 * @param int $pos
	 */
	private function displayError($pos = 0) {
		$total = sizeof($this->errors);

		if ($total === 0) {
			$this->runner->setInfoArray(
				[
					'errorCurrent' => 0,
					'errorTotal'   => 0,
				]
			);

			return;
		}

		$current = key($this->errors) + 1;
		$error = $this->getNavigationError($pos, ($current === 1), ($current === $total));
		$current = key($this->errors) + 1;

		if ($error === false) {
			return;
		}

		/** @var ModelIndex $index */
		$index = $error['index'];
		$errorIndex = '';
		if ($index !== null) {
			$errorIndex = $index->getProviderId() . ':' . $index->getDocumentId();
		}

		$width = $this->terminal->getWidth() - 13;
		$message = MiscService::get('message', $error, '');
		$err1 = substr($message, 0, $width);
		$err2 = substr($message, $width, $width + 10);
		$err3 = substr($message, $width + $width + 10, $width + 10);

		$this->runner->setInfoArray(
			[
				'errorCurrent'   => $current,
				'errorTotal'     => $total,
				'errorMessageA'  => trim($err1),
				'errorMessageB'  => trim($err2),
				'errorMessageC'  => trim($err3),
				'errorException' => MiscService::get('exception', $error, ''),
				'errorIndex'     => $errorIndex
			]
		);
	}


	/**
	 * @param int $pos
	 * @param bool $isFirst
	 * @param bool $isLast
	 *
	 * @return bool|array
	 */
	private function getNavigationError($pos, $isFirst, $isLast) {

		if ($pos === 0) {
			if ($this->navigateLastError === true) {
				return end($this->errors);
			} else {
				return current($this->errors);
			}
		}

		$this->navigateLastError = false;
		if ($pos === -99) {
			return reset($this->errors);
		}

		if ($pos === -1 && !$isFirst) {
			return prev($this->errors);
		}

		if ($pos === 1 && !$isLast) {
			return next($this->errors);
		}

		if ($pos === 99) {
			$this->navigateLastError = true;

			return end($this->errors);
		}

		return false;
	}


	/**
	 *
	 */
	private function deleteError() {
		$current = current($this->errors);
		if ($current === false) {
			return;
		}

		$this->runner->setInfoArray(
			[
				'errorMessageA'  => '',
				'errorMessageB'  => '',
				'errorMessageC'  => '',
				'errorException' => '',
				'errorIndex'     => ''
			]
		);

		$pos = key($this->errors);

		/** @var ModelIndex $index */
		$index = $current['index'];
		$this->indexService->resetErrorFromIndex($index);

		$errors = [];
		foreach ($this->errors as $error) {
			/** @var ModelIndex $errorIndex */
			$errorIndex = $error['index'];
			if ($index->getProviderId() === $errorIndex->getProviderId()
				&& $index->getDocumentId() === $errorIndex->getDocumentId()) {
				continue;
			}

			$errors[] = $error;
		}

		$this->errors = $errors;
		while (key($this->errors) < $pos) {
			if (next($this->errors) === false) {
				end($this->errors);
				break;
			}
		}

		$this->displayError();
	}
}



