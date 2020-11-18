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


namespace OCA\FullTextSearch\Command;


use Exception;
use OC\Core\Command\InterruptedException;
use OC\FullTextSearch\Model\DocumentAccess;
use OCA\FullTextSearch\ACommandBase;
use OCA\FullTextSearch\Exceptions\InterruptException;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotUniqueException;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Model\SearchResult;
use OCA\FullTextSearch\Provider\TestProvider;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCA\FullTextSearch\Service\TestService;
use OCP\AppFramework\QueryException;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IDocumentAccess;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Test
 *
 * @package OCA\FullTextSearch\Command
 */
class Test extends ACommandBase {

	const DELAY_STABILIZE_PLATFORM = 3;

	/** @var RunningService */
	private $runningService;

	/** @var PlatformService */
	private $platformService;

	/** @var ProviderService */
	private $providerService;

	/** @var IndexService */
	private $indexService;

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
	 * @param ProviderService $providerService
	 * @param IndexService $indexService
	 * @param PlatformService $platformService
	 * @param TestService $testService
	 * @param MiscService $miscService
	 */
	public function __construct(
		RunningService $runningService, PlatformService $platformService,
		ProviderService $providerService, IndexService $indexService, TestService $testService,
		MiscService $miscService
	) {
		parent::__construct();

		$this->runningService = $runningService;
		$this->platformService = $platformService;
		$this->providerService = $providerService;
		$this->indexService = $indexService;
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
			 ->addOption('json', 'j', InputOption::VALUE_NONE, 'return result as JSON')
			 ->addOption(
				 'platform_delay', 'd', InputOption::VALUE_REQUIRED,
				 'change DELAY_STABILIZE_PLATFORM'
			 );
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->isJson = ($input->getOption('json') === true);
		$platformDelay = ($input->getOption('platform_delay') > 0) ? (int)$input->getOption(
			'platform_delay'
		) : self::DELAY_STABILIZE_PLATFORM;

		$this->output($output, '.Testing your current setup:');

		try {
			$testProvider = $this->testCreatingProvider($output);
			$this->testMockedProvider($output, $testProvider);
			$testPlatform = $this->testLoadingPlatform($output);
			$this->testLockingProcess($output, $testPlatform, $testProvider);
		} catch (Exception $e) {
			$this->outputResult($output, false);
			throw $e;
		}

		try {
			$this->testResetTest($output, $testProvider);
			$this->pause($output, $platformDelay);
			$this->testInitIndexing($output, $testPlatform);
			$this->testIndexingDocuments($output, $testPlatform, $testProvider);
			$this->pause($output, $platformDelay);
			$this->testContentLicense($output, $testPlatform);
			$this->testSearchSimple($output, $testPlatform, $testProvider);
			$this->testUpdatingDocumentsAccess($output, $testPlatform, $testProvider);
			$this->pause($output, $platformDelay);
			$this->testSearchAccess($output, $testPlatform, $testProvider);
			$this->testSearchShare($output, $testPlatform, $testProvider);

			$this->testResetTest($output, $testProvider);
			$this->testUnlockingProcess($output);
		} catch (Exception $e) {
			$this->outputResult($output, false);
			$this->output($output, 'Error detected, unlocking process');
			$this->runner->stop();
			$this->outputResult($output, true);

			throw $e;
		}

		$this->output($output, '', true);

		return 0;
	}


	/**
	 * @return IFullTextSearchProvider
	 * @throws ProviderIsNotCompatibleException
	 * @throws QueryException
	 * @throws ProviderDoesNotExistException
	 * @throws ProviderIsNotUniqueException
	 */
	private function generateMockProvider(): IFullTextSearchProvider {
		$this->providerService->loadProvider(
			'fulltextsearch', 'OCA\FullTextSearch\Provider\TestProvider'
		);
		$providerWrapper = $this->providerService->getProvider(TestProvider::TEST_PROVIDER_ID);

		return $providerWrapper->getProvider();
	}


	/**
	 * @param OutputInterface $output
	 * @param string $line
	 * @param bool $isNewLine
	 */
	private function output(OutputInterface $output, string $line, bool $isNewLine = true) {
		if ($isNewLine) {
			$output->write(' ', true);
		}

		$output->write($line . ' ', false);
	}


	/**
	 * @param OutputInterface $output
	 * @param bool $result
	 */
	private function outputResult(OutputInterface $output, bool $result) {
		$isNewLine = false;
		$line = $this->convertBoolToLine($result, $isNewLine);

		$this->output($output, $line, $isNewLine);
	}


	/**
	 * @param bool $result
	 * @param bool $isNewLine
	 *
	 * @return string
	 */
	private function convertBoolToLine(bool $result, bool &$isNewLine): string {
		$isNewLine = false;
		if ($result === false) {
			return '<error>fail</error>';
		}

		return '<info>ok</info>';
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @return IFullTextSearchProvider
	 * @throws ProviderDoesNotExistException
	 * @throws ProviderIsNotCompatibleException
	 * @throws ProviderIsNotUniqueException
	 * @throws QueryException
	 */
	private function testCreatingProvider(OutputInterface $output): IFullTextSearchProvider {
		$this->output($output, 'Creating mocked content provider.');
		$testProvider = $this->generateMockProvider();
		$this->outputResult($output, true);

		return $testProvider;
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchProvider $testProvider
	 */
	private function testMockedProvider(
		OutputInterface $output, IFullTextSearchProvider $testProvider
	) {
		$this->output($output, 'Testing mocked provider: get indexable documents.');
		$testProvider->setIndexOptions(new IndexOptions());
		$indexableDocuments =
			$testProvider->generateIndexableDocuments(TestService::DOCUMENT_USER1, '');
		$this->output($output, '(' . sizeof($indexableDocuments) . ' items)', false);
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @return IFullTextSearchPlatform
	 * @throws Exception
	 */
	private function testLoadingPlatform(OutputInterface $output): IFullTextSearchPlatform {
		$this->output($output, 'Loading search platform.');
		$wrapper = $this->platformService->getPlatform();
		$testPlatform = $wrapper->getPlatform();

		$this->output($output, '(' . $testPlatform->getName() . ')', false);
		$this->outputResult($output, true);

		$this->output($output, 'Testing search platform.');
		if (!$testPlatform->testPlatform()) {
			throw new Exception ('Search platform (' . $testPlatform->getName() . ') down ?');
		}
		$this->outputResult($output, true);

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
		$this->runner->sourceIsCommandLine($this, $output);
		$this->runner->start();
		$this->indexService->setRunner($this->runner);
		$testPlatform->setRunner($this->runner);
		$testProvider->setRunner($this->runner);
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchProvider $testProvider
	 *
	 * @throws Exception
	 */
	private function testResetTest(OutputInterface $output, IFullTextSearchProvider $testProvider
	) {
		$this->output($output, 'Removing test.');
		$this->indexService->resetIndex($testProvider->getId());
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 */
	private function testInitIndexing(OutputInterface $output, IFullTextSearchPlatform $testPlatform
	) {
		$this->output($output, 'Initializing index mapping.');
		$testPlatform->initializeIndex();
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 *
	 * @throws Exception
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
			$testPlatform, $testProvider, TestService::DOCUMENT_USER1, $options
		);
		$this->outputResult($output, true);
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
				TestProvider::TEST_PROVIDER_ID, TestService::DOCUMENT_TYPE_LICENSE
			);

			$this->output(
				$output, '(size: ' . $indexDocument->getContentSize() . ')', false
			);
			$this->outputResult($output, true);
		} catch (Exception $e) {
			throw new Exception(
				"Issue while getting test document '" . TestService::DOCUMENT_TYPE_LICENSE
				. "' from search platform: " . $e->getMessage()
			);
		}

		$this->output($output, 'Comparing document with source.');
		$this->testService->compareIndexDocument(
			$this->testService->generateIndexDocumentContentLicense(new IndexOptions()),
			$indexDocument
		);
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 *
	 * @throws Exception
	 */
	private function testSearchSimple(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider
	) {

		$this->output($output, 'Searching basic keywords:');

		$access = new DocumentAccess();
		$access->setViewerId(TestService::DOCUMENT_USER1);

		$this->search(
			$output, $testPlatform, $testProvider, $access, 'test',
			[TestService::DOCUMENT_TYPE_SIMPLE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a simple test',
//			[TestService::DOCUMENT_TYPE_SIMPLE]
			[TestService::DOCUMENT_TYPE_SIMPLE, TestService::DOCUMENT_TYPE_LICENSE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, '"document is a test"',
			[]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, '"document is a simple test"',
			[TestService::DOCUMENT_TYPE_SIMPLE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a simple -test',
			[TestService::DOCUMENT_TYPE_LICENSE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a simple +test',
			[TestService::DOCUMENT_TYPE_SIMPLE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, '-document is a simple test',
			[]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a simple +test +testing',
			[TestService::DOCUMENT_TYPE_SIMPLE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a simple +test -testing',
			[]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a +simple -test -testing',
			[]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, '+document is a simple -test -testing',
			[TestService::DOCUMENT_TYPE_LICENSE]
		);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'document is a +simple -license +testing',
			[TestService::DOCUMENT_TYPE_SIMPLE]
		);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 *
	 * @throws Exception
	 */
	private function testUpdatingDocumentsAccess(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider
	) {
		$this->output($output, 'Updating documents access.');
		$options = new IndexOptions(
			[
				'provider'                            => TestProvider::TEST_PROVIDER_ID,
				TestService::DOCUMENT_INDEXING_OPTION => TestService::DOCUMENT_INDEXING_ACCESS
			]
		);
		$testProvider->setIndexOptions($options);
		$this->indexService->indexProviderContentFromUser(
			$testPlatform, $testProvider, TestService::DOCUMENT_USER1, $options
		);
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 *
	 * @throws Exception
	 */
	private function testSearchAccess(
		OutputInterface $output, IFullTextSearchPlatform $platform,
		IFullTextSearchProvider $provider
	) {
		$this->output($output, 'Searching with group access rights:');

		$this->searchGroups($output, $platform, $provider, [], []);
		$this->searchGroups(
			$output, $platform, $provider, [TestService::DOCUMENT_GROUP1],
			[TestService::DOCUMENT_TYPE_LICENSE]
		);
		$this->searchGroups(
			$output, $platform, $provider,
			[TestService::DOCUMENT_GROUP1, TestService::DOCUMENT_GROUP2],
			[TestService::DOCUMENT_TYPE_LICENSE]
		);
		$this->searchGroups(
			$output, $platform, $provider,
			[TestService::DOCUMENT_NOTGROUP, TestService::DOCUMENT_GROUP2],
			[TestService::DOCUMENT_TYPE_LICENSE]
		);
		$this->searchGroups($output, $platform, $provider, [TestService::DOCUMENT_NOTGROUP], []);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $platform
	 * @param IFullTextSearchProvider $provider
	 *
	 * @throws Exception
	 */
	private function testSearchShare(
		OutputInterface $output, IFullTextSearchPlatform $platform,
		IFullTextSearchProvider $provider
	) {

		$this->output($output, 'Searching with share rights:');

		$this->searchUsers($output, $platform, $provider, TestService::DOCUMENT_NOTUSER, []);
		$this->searchUsers($output, $platform, $provider, TestService::DOCUMENT_USER2, ['license']);
		$this->searchUsers($output, $platform, $provider, TestService::DOCUMENT_USER3, ['license']);
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws TickDoesNotExistException
	 */
	private function testUnlockingProcess(OutputInterface $output) {
		$this->output($output, 'Unlocking process');
		$this->runner->stop();
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 * @param IDocumentAccess $access
	 * @param string $search
	 * @param array $expected
	 * @param string $moreOutput
	 *
	 * @throws Exception
	 */
	private function search(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider,
		IDocumentAccess $access, string $search, array $expected, string $moreOutput = ''
	) {
		$this->output(
			$output,
			" - '" . $search . "'" . (($moreOutput === '') ? '' : ' - ' . $moreOutput . ' - ')
		);
		$request = new SearchRequest();

		$request->setSearch($search);

		$searchResult = new SearchResult($request);
		$searchResult->setProvider($testProvider);
		$searchResult->setPlatform($testPlatform);

		$testPlatform->searchRequest($searchResult, $access);

		$this->output(
			$output,
			'(result: ' . $searchResult->getCount() . ', expected: ' . json_encode($expected) . ')',
			false
		);
		$this->compareSearchResult($searchResult, $expected);
		$this->outputResult($output, true);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 * @param array $groups
	 * @param array $expected
	 *
	 * @throws Exception
	 */
	private function searchGroups(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider, array $groups, array $expected
	) {

		$access = new DocumentAccess();
		$access->setViewerId(TestService::DOCUMENT_NOTUSER);
		$access->setGroups($groups);

		$this->search(
			$output, $testPlatform, $testProvider, $access, 'license',
			$expected, json_encode($groups)
		);
	}


	/**
	 * @param OutputInterface $output
	 * @param IFullTextSearchPlatform $testPlatform
	 * @param IFullTextSearchProvider $testProvider
	 * @param string $user
	 * @param array $expected
	 *
	 * @throws Exception
	 */
	private function searchUsers(
		OutputInterface $output, IFullTextSearchPlatform $testPlatform,
		IFullTextSearchProvider $testProvider, string $user, array $expected
	) {
		$access = new DocumentAccess();
		$access->setViewerId($user);
		$this->search(
			$output, $testPlatform, $testProvider, $access, 'license',
			$expected, $user
		);
	}


	/**
	 * @param SearchResult $searchResult
	 * @param array $entries
	 *
	 * @throws Exception
	 */
	private function compareSearchResult(SearchResult $searchResult, array $entries) {
		$documents = $searchResult->getDocuments();
		if (sizeof($documents) !== sizeof($entries)) {
			throw new Exception('Unexpected SearchResult: ' . json_encode($searchResult));
		}

		foreach ($documents as $document) {
			if (!in_array($document->getId(), $entries)) {
				throw new Exception('Unexpected Document: ' . json_encode($document));
			}
		}
	}


	/**
	 * @param OutputInterface $output
	 * @param int $s
	 *
	 * @throws InterruptException
	 */
	private function pause(OutputInterface $output, int $s) {
		$this->output($output, 'Pausing ' . $s . ' seconds');

		for ($i = 1; $i <= $s; $i++) {
			if (time_nanosleep(1, 0) !== true) {
				throw new InterruptException('Interrupted by user');
			}

			$this->output($output, (string)$i, false);
		}

		$this->outputResult($output, true);
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

