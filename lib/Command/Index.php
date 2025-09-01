<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\InterruptedException;
use OCA\FullTextSearch\ACommandBase;
use OCA\FullTextSearch\Exceptions\PlatformTemporaryException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Index as ModelIndex;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\CliService;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\IUserManager;
use OutOfBoundsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Throwable;

class Index extends ACommandBase {
	use TArrayTools;

	const INDEX_OPTION_NO_READLINE = '_no-readline';

//			'%job:1s%%message:-40s%%current:6s%/%max:6s% [%bar%] %percent:3s%% \n %duration% %infos:-12s% %jvm:-30s%      '
	const PANEL_RUN = 'run';
	const PANEL_RUN_LINE_OPTIONS = 'Options: %options%';
	const PANEL_RUN_LINE_MEMORY = 'Memory: %_memory%';

	const PANEL_INDEX = 'indexing';
	const PANEL_INDEX_LINE_HEADER = '┌─ Indexing %_paused% ────';
	const PANEL_INDEX_LINE_ACCOUNT = '│ Provider: <info>%providerName:-20s%</info> Account: <info>%userId%</info>';
	const PANEL_INDEX_LINE_ACTION = '│ Action: <info>%action%</info>';
	const PANEL_INDEX_LINE_DOCUMENT = '│ Document: <info>%documentId%</info>';
	const PANEL_INDEX_LINE_INFO = '│ Info: <info>%info%</info>';
	const PANEL_INDEX_LINE_TITLE = '│ Title: <info>%title%</info>';
	const PANEL_INDEX_LINE_CONTENT = '│ Content size: <info>%content%</info>';
	const PANEL_INDEX_LINE_CHUNK = '│ Chunk: %chunkCurrent:6s%/%chunkTotal%';
	const PANEL_INDEX_LINE_PROGRESS = '│ Progress: %documentCurrent:6s%/%documentTotal%';
	const PANEL_INDEX_LINE_FOOTER = '└──';

	const PANEL_RESULT = 'result';
	const PANEL_RESULT_LINE_HEADER = '┌─ Results ────';
	const PANEL_RESULT_LINE_RESULT = '│ Result: <info>%resultCurrent:6s%</info>/<info>%resultTotal%</info>';
	const PANEL_RESULT_LINE_INDEX = '│ Index: <info>%resultIndex%</info>';
	const PANEL_RESULT_LINE_STATUS = '│ Status: %resultStatusColored%';
	const PANEL_RESULT_LINE_MESSAGE1 = '│ Message: <info>%resultMessageA%</info>';
	const PANEL_RESULT_LINE_MESSAGE2 = '│ <info>%resultMessageB%</info>';
	const PANEL_RESULT_LINE_MESSAGE3 = '│ <info>%resultMessageC%</info>';
	const PANEL_RESULT_LINE_FOOTER = '└──';

	const PANEL_ERRORS = 'errors';
	const PANEL_ERRORS_LINE_HEADER = '┌─ Errors ────';
	const PANEL_ERRORS_LINE_ERRORS = '│ Error: <comment>%errorCurrent:6s%</comment>/<comment>%errorTotal%</comment>';
	const PANEL_ERRORS_LINE_ERROR_INDEX = '│ Index: <comment>%errorIndex%</comment>';
	const PANEL_ERRORS_LINE_ERROR_EXCEPTION = '│ Exception: <comment>%errorException%</comment>';
	const PANEL_ERRORS_LINE_ERROR_MESSAGE1 = '│ Message: <comment>%errorMessageA%</comment>';
	const PANEL_ERRORS_LINE_ERROR_MESSAGE2 = '│ <comment>%errorMessageB%</comment>';
	const PANEL_ERRORS_LINE_ERROR_MESSAGE3 = '│ <comment>%errorMessageC%</comment>';
	const PANEL_ERRORS_LINE_FOOTER = '└──';

	const PANEL_COMMANDS_ROOT = 'root';
	const PANEL_COMMANDS_ROOT_LINE = '## q:quit ## p:pause ';
	const PANEL_COMMANDS_PAUSED = 'paused';
	const PANEL_COMMANDS_PAUSED_LINE = '## q:quit ## u:unpause ## n:next step';
	const PANEL_COMMANDS_DONE = 'done';
	const PANEL_COMMANDS_DONE_LINE = '## q:quit';
	const PANEL_COMMANDS_NAVIGATION = 'navigation';
	const PANEL_COMMANDS_ERRORS_LINE = '## f:first error ## h/j:prec/next error ## d:delete error ## l:last error';
	const PANEL_COMMANDS_RESULTS_LINE = '## x:first result ## c/v:prec/next result ## b:last result';


	/** @var Runner */
	private $runner;

	/** @var Terminal */
	private $terminal;

	/** @var array */
	private $results = [];

	/** @var bool */
	private $navigateLastResult = true;

	/** @var array */
	private $errors = [];

	/** @var bool */
	private $navigateLastError = true;

	public function __construct(
		private IUserManager $userManager,
		private RunningService $runningService,
		private CliService $cliService,
		private IndexService $indexService,
		private PlatformService $platformService,
		private ProviderService $providerService,
		private ConfigService $configService
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:index')
			 ->setDescription('Index files')
			 ->addArgument('options', InputArgument::OPTIONAL, 'options')
			 ->addOption(
				 'no-readline', 'r', InputOption::VALUE_NONE,
				 'disable readline - non interactive mode'
			 );
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

		if ($options->getOptionBool(self::INDEX_OPTION_NO_READLINE, false) === false) {
			/** do not get stuck while waiting interactive input */
			try {
				readline_callback_handler_install(
					'', function () {
				}
				);
			} catch (Throwable $t) {
				throw new Exception('Please install php-readline, or use --no-readline');
			}
		}

		stream_set_blocking(STDIN, false);

		$this->terminal = new Terminal();

//		$outputStyle = new OutputFormatterStyle('white', 'black', ['bold']);
//		$output->getFormatter()
//			   ->setStyle('char', $outputStyle);

		$this->runner = new Runner($this->runningService, 'commandIndex', ['nextStep' => 'n']);
		$this->runner->onKeyPress([$this, 'onKeyPressed']);
		$this->runner->onNewIndexError([$this, 'onNewIndexError']);
		$this->runner->onNewIndexResult([$this, 'onNewIndexResult']);
		$this->runner->pause($options->getOptionBool('paused', false));

		$this->indexService->setRunner($this->runner);
		$this->cliService->setRunner($this->runner);

		$this->generatePanels($options);
		$this->runner->setInfo('options', json_encode($options));

		try {
			$this->runner->sourceIsCommandLine($this, $output);
			$this->runner->start();

			if ($options->getOption('errors') === 'reset') {
				$this->indexService->resetErrorsAll();
			}

			$this->testPlatform();
			$this->cliService->runDisplay($output);
			$this->generateIndexErrors();
			$this->displayError();
			$this->displayResult();

			$providers = $this->providerService->getProviders();
			foreach ($providers as $providerWrapper) {
				$provider = $providerWrapper->getProvider();

				if (!$this->isIncludedProvider($options, $provider->getId())) {
					continue;
				}

				$provider->setRunner($this->runner);
				$provider->setIndexOptions($options);
				$this->indexProvider($provider, $options);
			}

		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

		$this->runner->setInfo('documentCurrent', 'all');
		$this->runner->stop();

		return 0;
	}


	/**
	 * @param string $key
	 */
	public function onKeyPressed(string $key) {
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

		if ($key === 'x') {
			$this->displayResult(-99);
		}
		if ($key === 'c') {
			$this->displayResult(-1);
		}
		if ($key === 'v') {
			$this->displayResult(1);
		}
		if ($key === 'b') {
			$this->displayResult(99);
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
	public function onNewIndexError(array $error) {
		$this->errors[] = $error;
		$this->displayError();
	}


	/**
	 * @param array $result
	 */
	public function onNewIndexResult(array $result) {
		$this->results[] = $result;
		$this->displayResult();
	}


	/**
	 * @throws Exception
	 */
	private function testPlatform() {
		$wrapper = $this->platformService->getPlatform();
		$platform = $wrapper->getPlatform();

		if (!$platform->testPlatform()) {
			throw new Exception('failed platform test.');
		}
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IndexOptions $options
	 *
	 * @throws Exception
	 */
	private function indexProvider(IFullTextSearchProvider $provider, IndexOptions $options) {
		$wrapper = $this->platformService->getPlatform();
		$platform = $wrapper->getPlatform();

		$platform->initializeIndex();
		$provider->onInitializingIndex($platform);

		$platform->setRunner($this->runner);

		$users = $this->generateUserList($options);
		foreach ($users as $user) {
			if ($user === null) {
				continue;
			}

			try {
				$this->indexService->indexProviderContentFromUser($platform, $provider, $user->getUID(), $options);
			} catch (PlatformTemporaryException $e) {
				throw $e;
			} catch (Exception $e) {
				continue;
			}
		}
	}


	/**
	 * @param InputInterface $input
	 *
	 * @return IndexOptions
	 */
	private function generateIndexOptions(InputInterface $input): IndexOptions {
		$jsonOptions = $input->getArgument('options');

		$options = [];
		if (is_string($jsonOptions)) {
			$options = json_decode($jsonOptions, true);
		}

		if (!is_array($options)) {
			$options = [];
		}

		if ($input->getOption('no-readline')) {
			$options['_no-readline'] = true;
		}

		return new IndexOptions($options);
	}


	/**
	 * @param IndexOptions $options
	 * @param string $providerId
	 *
	 * @return bool
	 */
	private function isIncludedProvider(IndexOptions $options, string $providerId): bool {
		if ($options->getOption('provider', '') !== ''
			&& $options->getOption('provider') !== $providerId) {
			return false;
		}

		if ($options->getOptionArray('providers', []) !== []) {
			return (in_array($providerId, $options->getOptionArray('providers', [])));
		}

		return true;
	}


	/**
	 * @param IndexOptions $options
	 *
	 * @return array
	 */
	private function generateUserList(IndexOptions $options): array {
		if ($options->getOption('user', '') !== '') {
			return [$this->userManager->get($options->getOption('user'))];
		}

		if ($options->getOptionArray('users', []) !== []) {
			return array_map([$this->userManager, 'get'], $options->getOptionArray('users'));
		}

		return $this->userManager->search('');
	}


	/**
	 * @param IndexOptions $options
	 */
	private function generatePanels(IndexOptions $options) {

		$this->cliService->createPanel(
			self::PANEL_RUN,
			[
				self::PANEL_RUN_LINE_OPTIONS,
				self::PANEL_RUN_LINE_MEMORY
			]
		);

		$this->cliService->createPanel(
			self::PANEL_INDEX, [
								 self::PANEL_INDEX_LINE_HEADER,
								 self::PANEL_INDEX_LINE_ACTION,
								 self::PANEL_INDEX_LINE_ACCOUNT,
								 self::PANEL_INDEX_LINE_DOCUMENT,
								 self::PANEL_INDEX_LINE_INFO,
								 self::PANEL_INDEX_LINE_TITLE,
								 self::PANEL_INDEX_LINE_CONTENT,
								 self::PANEL_INDEX_LINE_CHUNK,
								 self::PANEL_INDEX_LINE_PROGRESS,
								 self::PANEL_INDEX_LINE_FOOTER,
							 ]
		);

		$this->cliService->createPanel(
			self::PANEL_RESULT, [
								  self::PANEL_RESULT_LINE_HEADER,
								  self::PANEL_RESULT_LINE_RESULT,
								  self::PANEL_RESULT_LINE_INDEX,
								  self::PANEL_RESULT_LINE_STATUS,
								  self::PANEL_RESULT_LINE_MESSAGE1,
								  self::PANEL_RESULT_LINE_MESSAGE2,
								  self::PANEL_RESULT_LINE_MESSAGE3,
								  self::PANEL_RESULT_LINE_FOOTER,
							  ]
		);

		$this->cliService->createPanel(
			self::PANEL_ERRORS, [
								  self::PANEL_ERRORS_LINE_HEADER,
								  self::PANEL_ERRORS_LINE_ERRORS,
								  self::PANEL_ERRORS_LINE_ERROR_INDEX,
								  self::PANEL_ERRORS_LINE_ERROR_EXCEPTION,
								  self::PANEL_ERRORS_LINE_ERROR_MESSAGE1,
								  self::PANEL_ERRORS_LINE_ERROR_MESSAGE2,
								  self::PANEL_ERRORS_LINE_ERROR_MESSAGE3,
								  self::PANEL_ERRORS_LINE_FOOTER,
							  ]
		);

		$this->cliService->createPanel(
			self::PANEL_COMMANDS_PAUSED, [
										   self::PANEL_COMMANDS_PAUSED_LINE
									   ]
		);

		$this->cliService->createPanel(
			self::PANEL_COMMANDS_ROOT, [
										 self::PANEL_COMMANDS_ROOT_LINE
									 ]
		);

		$this->cliService->createPanel(
			self::PANEL_COMMANDS_NAVIGATION, [
											   self::PANEL_COMMANDS_RESULTS_LINE,
											   self::PANEL_COMMANDS_ERRORS_LINE
										   ]
		);

		$this->cliService->initDisplay();
		$this->cliService->displayPanel('run', self::PANEL_RUN);
		$this->cliService->displayPanel('indexPanel', self::PANEL_INDEX);
		$this->cliService->displayPanel('resultsPanel', self::PANEL_RESULT);
		$this->cliService->displayPanel('errorsPanel', self::PANEL_ERRORS);

		if ($options->getOptionBool(self::INDEX_OPTION_NO_READLINE, false) === false) {
			$this->cliService->displayPanel('navigation', self::PANEL_COMMANDS_NAVIGATION);
			if ($this->runner->isPaused()) {
				$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_PAUSED);
			} else {
				$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_ROOT);
			}
		}

		// full list of info that can be edited
		$this->runner->setInfoArray(
			[
				'userId' => '',
				'providerName' => '',
				'_memory' => '',
				'documentId' => '',
				'action' => '',
				'info' => '',
				'title' => '',
				'_paused' => '',

				'resultIndex' => '',
				'resultCurrent' => '',
				'resultTotal' => '',
				'resultMessageA' => '',
				'resultMessageB' => '',
				'resultMessageC' => '',
				'resultStatus' => '',
				'resultStatusColored' => '',
				'content' => '',
				'statusColored' => '',
				'chunkCurrent' => '',
				'chunkTotal' => '',
				'documentCurrent' => '',
				'documentTotal' => '',
				'progressStatus' => '',
				'errorCurrent' => '0',
				'errorTotal' => '0',
				'errorMessageA' => '',
				'errorMessageB' => '',
				'errorMessageC' => '',
				'errorException' => '',
				'errorIndex' => ''
			]
		);
	}


	/**
	 * @param int $pos
	 */
	private function displayError(int $pos = 0) {
		$total = sizeof($this->errors);

		if ($total === 0) {
			$this->runner->setInfoArray(
				[
					'errorCurrent' => 0,
					'errorTotal' => 0,
				]
			);

			return;
		}

		try {
			$current = key($this->errors) + 1;
			$error = $this->getNavigationError($pos, ($current === 1), ($current === $total));
			$current = key($this->errors) + 1;
		} catch (OutOfBoundsException $e) {
			return;
		}

		/** @var ModelIndex $index */
		$index = $error['index'];
		$errorIndex = '';
		if ($index !== null) {
			$errorIndex = $index->getProviderId() . ':' . $index->getDocumentId();
		}

		$width = $this->terminal->getWidth() - 13;
		$message = $this->get('message', $error, '');
		$err1 = (string)substr($message, 0, $width);
		$err2 = (string)substr($message, $width, $width + 10);
		$err3 = (string)substr($message, $width + $width + 10, $width + 10);

		$this->runner->setInfoArray(
			[
				'errorCurrent' => $current,
				'errorTotal' => $total,
				'errorMessageA' => trim($err1),
				'errorMessageB' => trim($err2),
				'errorMessageC' => trim($err3),
				'errorException' => $this->get('exception', $error, ''),
				'errorIndex' => $errorIndex
			]
		);
	}


	/**
	 * @param int $pos
	 */
	private function displayResult(int $pos = 0) {
		$total = sizeof($this->results);

		if ($total === 0) {
			$this->runner->setInfoArray(
				[
					'resultCurrent' => 0,
					'resultTotal' => 0,
				]
			);

			return;
		}

		try {
			$current = key($this->results) + 1;
			$result = $this->getNavigationResult($pos, ($current === 1), ($current === $total));
			$current = key($this->results) + 1;
		} catch (OutOfBoundsException $e) {
			return;
		}

		/** @var ModelIndex $index */
		$index = $result['index'];
		$resultIndex = '';
		if ($index !== null) {
			$resultIndex = $index->getProviderId() . ':' . $index->getDocumentId();
		}


		$width = $this->terminal->getWidth() - 13;
		$message = $this->get('message', $result, '');
		$msg1 = (string)substr($message, 0, $width);
		$msg2 = (string)substr($message, $width, $width + 10);
		$msg3 = (string)substr($message, $width + $width + 10, $width + 10);

		$status = $this->get('status', $result, '');
		$type = $this->getInt('type', $result, 0);

		$this->runner->setInfoArray(
			[
				'resultCurrent' => $current,
				'resultTotal' => $total,
				'resultMessageA' => trim($msg1),
				'resultMessageB' => trim($msg2),
				'resultMessageC' => trim($msg3),
				'resultStatus' => $status,
				'resultIndex' => $resultIndex
			]
		);
		$this->runner->setInfoColored('resultStatus', $type);
	}


	/**
	 * @param int $pos
	 * @param bool $isFirst
	 * @param bool $isLast
	 *
	 * @throw OutOfBoundsException
	 * @return array
	 */
	private function getNavigationError(int $pos, bool $isFirst, bool $isLast): array {

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

		throw new OutOfBoundsException();
	}


	/**
	 * @param int $pos
	 * @param bool $isFirst
	 * @param bool $isLast
	 *
	 * @throw OutOfBoundsException
	 * @return array
	 */
	private function getNavigationResult(int $pos, bool $isFirst, bool $isLast): array {

		if ($pos === 0) {
			if ($this->navigateLastResult === true) {
				return end($this->results);
			} else {
				return current($this->results);
			}
		}

		$this->navigateLastResult = false;
		if ($pos === -99) {
			return reset($this->results);
		}

		if ($pos === -1 && !$isFirst) {
			return prev($this->results);
		}

		if ($pos === 1 && !$isLast) {
			return next($this->results);
		}

		if ($pos === 99) {
			$this->navigateLastResult = true;

			return end($this->results);
		}

		throw new OutOfBoundsException();
	}


	/**
	 *
	 */
	private function generateIndexErrors() {
		$indexes = $this->indexService->getErrorIndexes();

		foreach ($indexes as $index) {
			foreach ($index->getErrors() as $error) {
				$this->errors[] = [
					'index' => $index,
					'message' => $error['message'],
					'exception' => $error['exception'],
					'severity' => $error['severity']
				];
			}

		}
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
				'errorMessageA' => '',
				'errorMessageB' => '',
				'errorMessageC' => '',
				'errorException' => '',
				'errorIndex' => ''
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


	/**
	 * @throws TickDoesNotExistException
	 */
	public function abort() {
		try {
			$this->abortIfInterrupted();
		} catch (InterruptedException $e) {
			$this->runner->stop();
			exit();
		}
	}


}

