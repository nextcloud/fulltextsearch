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
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\ExtendedBase;
use OCA\FullTextSearch\Model\Index as ModelIndex;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\CliService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\IUserManager;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;


class Index extends ExtendedBase {

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
	const PANEL_COMMANDS_ROOT_LINE = '## <char>q</char>:quit ## <char>p</char>:pause ';
	const PANEL_COMMANDS_PAUSED = 'paused';
	const PANEL_COMMANDS_PAUSED_LINE = '## <char>q</char>:quit ## <char>u</char>:unpause ## <char>n</char>:next step';
	const PANEL_COMMANDS_DONE = 'done';
	const PANEL_COMMANDS_DONE_LINE = '## <char>q</char>:quit';
	const PANEL_COMMANDS_NAVIGATION = 'navigation';
	const PANEL_COMMANDS_ERRORS_LINE = '## <char>f</char>:first error ## <char>h</char>/<char>j</char>:prec/next error ## <char>d</char>:delete error ## <char>l</char>:last error';
	const PANEL_COMMANDS_RESULTS_LINE = '## <char>x</char>:first result ## <char>c</char>/<char>v</char>:prec/next result ## <char>b</char>:last result';

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

		$options = $this->generateIndexOptions($input);

		$this->runner = new Runner($this->runningService, 'commandIndex', ['nextStep' => 'n']);
		$this->runner->onKeyPress([$this, 'onKeyPressed']);
		$this->runner->onNewIndexError([$this, 'onNewIndexError']);
		$this->runner->onNewIndexResult([$this, 'onNewIndexResult']);
		$this->runner->pause($options->getOptionBool('paused', false));

		$this->indexService->setRunner($this->runner);
		$this->cliService->setRunner($this->runner);

		$this->generatePanels();
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

//		while (true) {
//			$this->runner->updateAction('_indexOver', true);
//			$pressed = strtolower($this->updateAction(''));
//			if ($pressed === $this->keys['nextStep']) {
//				$this->pauseRunning(false);
//				break;
//			}
//			usleep(300000);
//		}


//		$output->writeLn('');

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
	public function onNewIndexError($error) {
		$this->errors[] = $error;
		$this->displayError();
	}


	/**
	 * @param array $result
	 */
	public function onNewIndexResult($result) {
		$this->results[] = $result;
		$this->displayResult();
	}


	/**
	 * @throws Exception
	 */
	private function testPlatform() {
		$platform = $this->platformService->getPlatform();
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
		$platform = $this->platformService->getPlatform();
		$platform->initializeIndex();
		$provider->onInitializingIndex($platform);

		$platform->setRunner($this->runner);

		$users = $this->generateUserList($options);
		foreach ($users as $user) {
			if ($user === null) {
				continue;
			}

			try {
				$this->indexService->indexProviderContentFromUser(
					$platform, $provider, $user->getUID(), $options
				);
			} catch (Exception $e) {
				continue;
			}
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


	/**
	 *
	 */
	private function generatePanels() {

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
		$this->cliService->displayPanel('navigation', self::PANEL_COMMANDS_NAVIGATION);

		if ($this->runner->isPaused()) {
			$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_PAUSED);
		} else {
			$this->cliService->displayPanel('commands', self::PANEL_COMMANDS_ROOT);
		}

		$this->runner->setInfoArray(
			[
				'userId'       => '',
				'providerName' => '',
				'_memory'      => '',
				'documentId'   => '',
				'action'       => '',
				'info'         => '',
				'title'        => '',
				'_paused'      => '',

				'resultIndex'         => '',
				'resultCurrent'       => '',
				'resultTotal'         => '',
				'resultMessageA'      => '',
				'resultMessageB'      => '',
				'resultMessageC'      => '',
				'resultStatus'        => '',
				'resultStatusColored' => '',
				'content'             => '',
				'statusColored'       => '',
				'documentCurrent'     => '',
				'documentTotal'       => '',
				'progressStatus'      => '',
				'errorCurrent'        => '0',
				'errorTotal'          => '0',
				'errorMessageA'       => '',
				'errorMessageB'       => '',
				'errorMessageC'       => '',
				'errorException'      => '',
				'errorIndex'          => ''
			]
		);
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
	 */
	private function displayResult($pos = 0) {
		$total = sizeof($this->results);

		if ($total === 0) {
			$this->runner->setInfoArray(
				[
					'resultCurrent' => 0,
					'resultTotal'   => 0,
				]
			);

			return;
		}

		$current = key($this->results) + 1;
		$result = $this->getNavigationResult($pos, ($current === 1), ($current === $total));
		$current = key($this->results) + 1;

		if ($result === false) {
			return;
		}

		/** @var ModelIndex $index */
		$index = $result['index'];
		$resultIndex = '';
		if ($index !== null) {
			$resultIndex = $index->getProviderId() . ':' . $index->getDocumentId();
		}


		$width = $this->terminal->getWidth() - 13;
		$message = MiscService::get('message', $result, '');
		$msg1 = substr($message, 0, $width);
		$msg2 = substr($message, $width, $width + 10);
		$msg3 = substr($message, $width + $width + 10, $width + 10);


		$status = MiscService::get('status', $result, '');
		$type = MiscService::get('type', $result, '');

		$this->runner->setInfoArray(
			[
				'resultCurrent'  => $current,
				'resultTotal'    => $total,
				'resultMessageA' => trim($msg1),
				'resultMessageB' => trim($msg2),
				'resultMessageC' => trim($msg3),
				'resultStatus'   => $status,
				'resultIndex'    => $resultIndex
			]
		);
		$this->runner->setInfoColored('resultStatus', $type);
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
	 * @param int $pos
	 * @param bool $isFirst
	 * @param bool $isLast
	 *
	 * @return bool|array
	 */
	private function getNavigationResult($pos, $isFirst, $isLast) {

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

		return false;
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
					'severity'  => $error['severity']
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



