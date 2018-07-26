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
use OCA\FullTextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\IFullTextSearchPlatform;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\ExtendedBase;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Provider\TestProvider;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\RunningService;
use OCA\FullTextSearch\Service\TestService;
use OCP\AppFramework\QueryException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Test extends ExtendedBase {

	/** @var RunningService */
	private $runningService;

	/** @var IndexService */
	private $indexService;

	/** @var PlatformService */
	private $platformService;

	/** @var TestService */
	private $testService;

	/** @var MiscService */
	private $miscService;


	/** @var Runner */
	private $runner;


	/** @var boolean */
	private $isJson = false;

	/**
	 * Index constructor.
	 *
	 * @param RunningService $runningService
	 * @param IndexService $indexService
	 * @param PlatformService $platformService
	 * @param TestService $testService
	 * @param MiscService $miscService
	 */
	public function __construct(
		RunningService $runningService, IndexService $indexService,
		PlatformService $platformService, TestService $testService, MiscService $miscService
	) {
		parent::__construct();

		$this->indexService = $indexService;
		$this->runningService = $runningService;
		$this->platformService = $platformService;
		$this->testService = $testService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:test')
			 ->setDescription('Testing the platform setup')
			 ->addOption('json', 'j', InputOption::VALUE_NONE, 'return result as JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->isJson = ($input->getOption('json') === true);

		$this->output($output, '.Testing your current setup:');

		try {
			$testProvider = $this->testCreatingProvider($output);
			$this->testMockedProvider($output, $testProvider);
			$testPlatform = $this->testLoadingPlatform($output);
		} catch (Exception $e) {
			$this->output($output, false);
			throw $e;
		}

		try {
			$this->testLockingProcess($output, $testPlatform, $testProvider);
			$this->testIndexingDocuments($output, $testPlatform, $testProvider);
			$this->testContentLicense($output, $testPlatform);

			$this->testUnlockingProcess($output);
		} catch (Exception $e) {

			$this->output($output, false);
			$this->output($output, 'Error detected, unlocking process');
			$this->runner->stop();
			$this->output($output, true);

			throw $e;
		}


//		$options = $this->generateIndexOptions($input);
//
//		try {
//			$this->runner->sourceIsCommandLine($this, $output);
//			$this->runner->start();
//
//			$providers = $this->providerService->getProviders();
//			foreach ($providers as $provider) {
//
//				if (!$this->isIncludedProvider($options, $provider->getId())) {
//					continue;
//				}
//
//				$this->runner->output('indexing ' . $provider->getName() . '.');
//				$provider->setRunner($this->runner);
//				$provider->setIndexOptions($options);
//				$this->indexProvider($provider, $options);
//			}
//
//		} catch (Exception $e) {
//			$this->runner->exception($e->getMessage(), true);
//			throw $e;
//		}
//
//		$this->runner->stop();

		$this->output($output, '', true);
	}


	/**
	 * @return IFullTextSearchProvider
	 * @throws QueryException
	 * @throws ProviderIsNotCompatibleException
	 */
	private function generateMockProvider() {
		$providerId = 'OCA\FullTextSearch\Provider\TestProvider';
		$provider = \OC::$server->query((string)$providerId);
		if (!($provider instanceof IFullTextSearchProvider)) {
			throw new ProviderIsNotCompatibleException(
				'TestProvider is not a compatible IFullTextSearchProvider'
			);
		}

		return $provider;
	}


	/**
	 * @param OutputInterface $output
	 * @param string|bool $line
	 * @param bool $isNewLine
	 */
	private function output(OutputInterface $output, $line, $isNewLine = true) {
		$line = $this->convertBoolToLine($line, $isNewLine);
		if ($isNewLine) {
			$output->write(' ', true);
		}

		$output->write($line . ' ', false);
	}


	/**
	 * @param string|bool $line
	 * @param $isNewLine
	 *
	 * @return string
	 */
	private function convertBoolToLine($line, &$isNewLine) {
		if (!is_bool($line)) {
			return $line;
		}

		$isNewLine = false;
		if ($line === false) {
			return '<error>fail</error>';
		}

		return '<info>ok</info>';
	}


	/**
	 * @param $output
	 *
	 * @return IFullTextSearchProvider
	 * @throws ProviderIsNotCompatibleException
	 * @throws QueryException
	 */
	private function testCreatingProvider($output) {
		$this->output($output, 'Creating mocked content provider.');
		$testProvider = $this->generateMockProvider();
		$this->output($output, true);

		return $testProvider;
	}


	/**
	 * @param $output
	 * @param IFullTextSearchProvider $testProvider
	 */
	private function testMockedProvider($output, IFullTextSearchProvider $testProvider) {
		$this->output($output, 'Testing mocked provider: get indexable documents.');
		$indexableDocuments = $testProvider->generateIndexableDocuments('user');
		$this->output($output, '(' . sizeof($indexableDocuments) . ' items)', false);
		$this->output($output, true);
	}


	/**
	 * @param $output
	 *
	 * @return IFullTextSearchPlatform
	 * @throws Exception
	 */
	private function testLoadingPlatform($output) {
		$this->output($output, 'Loading search platform.');
		$testPlatform = $this->platformService->getPlatform();
		$this->output($output, '(' . $testPlatform->getName() . ')', false);
		$this->output($output, true);

		$this->output($output, 'Testing search platform.');
		$testPlatform->testPlatform();
		$this->output($output, true);

		return $testPlatform;
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 *
	 * @throws RunnerAlreadyUpException
	 */
	private function testLockingProcess(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider
	) {
		$this->output($output, 'Locking process');
		$this->runner = new Runner($this->runningService, 'test');
		$this->runner->start(true);
		$this->indexService->setRunner($this->runner);
		$testPlatform->setRunner($this->runner);
		$testProvider->setRunner($this->runner);
		$this->output($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 *
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	private function testIndexingDocuments(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider
	) {
		$this->output($output, 'Indexing generated documents.');
		$options = new IndexOptions(
			[
				'provider' => TestProvider::TEST_PROVIDER_ID
			]
		);
		$this->indexService->indexProviderContentFromUser(
			$testPlatform, $testProvider, 'user', $options
		);
		$this->output($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 *
	 * @throws Exception
	 */
	private function testContentLicense(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform
	) {

		try {
			$this->output($output, 'Retreiving content from a big index (license).');
			$indexDocument = $testPlatform->getDocument(
				TestProvider::TEST_PROVIDER_ID, TestService::DOCUMENT_TEST_LICENSE
			);

			$this->output(
				$output, '(size: ' . $indexDocument->getContentSize() . ')', false
			);
			$this->output($output, true);

		} catch (Exception $e) {
			throw new Exception(
				"Issue while getting test document '" . TestService::DOCUMENT_TEST_LICENSE
				. "' from search platform: " . $e->getMessage()
			);
		}

		$this->output($output, 'Comparing document with source.');
		$this->testService->compareIndexDocument(
			$this->testService->generateIndexDocumentContentLicense(), $indexDocument
		);
		$this->output($output, true);
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws TickDoesNotExistException
	 */
	private function testUnlockingProcess(OutputInterface $output) {
		$this->output($output, 'Unlocking process');
		$this->runner->stop();
		$this->output($output, true);
	}



//	/**
//	 * @param IFullTextSearchProvider $provider
//	 * @param IndexOptions $options
//	 *
//	 * @throws Exception
//	 */
//	private function indexProvider(IFullTextSearchProvider $provider, IndexOptions $options) {
//		$platform = $this->platformService->getPlatform();
//		$platform->initializeIndex();
//		$provider->onInitializingIndex($platform);
//
//		$platform->setRunner($this->runner);
//
//		$users = $this->generateUserList($options);
//		foreach ($users as $user) {
//			if ($user === null) {
//				continue;
//			}
//
//			$this->runner->output(' USER: ' . $user->getUID());
//			$this->indexService->indexProviderContentFromUser(
//				$platform, $provider, $user->getUID(), $options
//			);
//		}
//
//		$this->providerService->setProviderAsIndexed($provider, true);
//
//	}
//
//
//	/**
//	 * @param InputInterface $input
//	 *
//	 * @return IndexOptions
//	 */
//	private function generateIndexOptions(InputInterface $input) {
//		$jsonOptions = $input->getArgument('options');
//		$options = json_decode($jsonOptions, true);
//
//		if (!is_array($options)) {
//			$options = [];
//		}
//
//		return new IndexOptions($options);
//	}
//
//
//	/**
//	 * @param IndexOptions $options
//	 * @param string $providerId
//	 *
//	 * @return bool
//	 */
//	private function isIncludedProvider(IndexOptions $options, $providerId) {
//		if ($options->getOption('provider', '') !== ''
//			&& $options->getOption('provider') !== $providerId) {
//			return false;
//		}
//
//		if ($options->getOption('providers', null) !== null
//			&& is_array($options->getOption('providers'))) {
//			return (in_array($providerId, $options->getOption('providers')));
//		}
//
//		return true;
//	}
//
//
//	/**
//	 * @param IndexOptions $options
//	 *
//	 * @return array
//	 */
//	private function generateUserList(IndexOptions $options) {
//		if ($options->getOption('user', '') !== '') {
//			return [$this->userManager->get($options->getOption('user'))];
//		}
//
//		if ($options->getOption('users', null) !== null
//			&& is_array($options->getOption('users'))) {
//			return array_map([$this->userManager, 'get'], $options->getOption('users'));
//		}
//
//		return $this->userManager->search('');
//	}
}



