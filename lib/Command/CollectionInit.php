<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;


use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Model\IndexOptions;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\CliService;
use OCA\FullTextSearch\Service\CollectionService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CollectionInit extends Base {


	/** @var ProviderService */
	private $providerService;

	/** @var CollectionService */
	private $collectionService;

	/** @var IUserManager */
	private $userManager;

	/** @var RunningService */
	private $runningService;

	/** @var CliService */
	private $cliService;


	/**
	 * @param IUserManager $userManager
	 * @param CollectionService $collectionService
	 * @param ProviderService $providerService
	 * @param RunningService $runningService
	 * @param CliService $cliService
	 */
	public function __construct(
		IUserManager $userManager,
		CollectionService $collectionService,
		ProviderService $providerService,
		RunningService $runningService,
		CliService $cliService
	) {
		parent::__construct();

		$this->userManager = $userManager;
		$this->collectionService = $collectionService;
		$this->providerService = $providerService;
		$this->runningService = $runningService;
		$this->cliService = $cliService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:collection:init')
			 ->setDescription('Initiate a collection')
			 ->addArgument('name', InputArgument::REQUIRED, 'name of the collection');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$collection = $input->getArgument('name');
		$this->collectionService->confirmCollectionString($collection);

		$runner = new Runner($this->runningService, 'commandIndex', ['nextStep' => 'n']);
//		$runner->sourceIsCommandLine($this, $output);
		$this->collectionService->setRunner($runner);
		$this->cliService->setRunner($runner);

		$this->cliService->createPanel(
			'collection', [
							'┌─ Collection ' . $collection . ' ────',
							'│ ProviderId, UserId: <info>%providerId%</info> / <info>%userId%</info>',
							'│ Chunk: <info>%chunkCurr:3s%</info>/<info>%chunkTotal%</info>',
							'│ Document: <info>%documentCurr:6s%</info>/<info>%documentChunk%</info>',
							'│',
							'│ Total Document: <info>%documentTotal%</info>',
							'│ Index initiated: <info>%indexCount%</info>',
							'└──'
						]
		);

		$runner->setInfoArray([
								  'providerId' => '',
								  'userId' => '',
								  'chunkCurr' => '',
								  'chunkTotal' => '',
								  'documentCurr' => '',
								  'documentChunk' => '',
								  'documentTotal' => '',
								  'indexCount' => 0
							  ]);

		$this->cliService->initDisplay();
		$this->cliService->displayPanel('run', 'collection');
		$this->cliService->runDisplay($output);

		$providers = $this->providerService->getProviders();
		foreach ($providers as $providerWrapper) {
			$this->indexProvider($runner, $collection, $providerWrapper->getProvider());
		}


		return 0;
	}


	/**
	 * @param string $collection
	 * @param IFullTextSearchProvider $provider
	 *
	 * @throws Exception
	 */
	private function indexProvider(
		Runner $runner,
		string $collection,
		IFullTextSearchProvider $provider,
		string $userId = ''
	) {
		$runner->setInfo('providerId', $provider->getId());
		$options = new IndexOptions();
		$provider->setIndexOptions($options);

		if ($userId === '') {
			$users = $this->userManager->search('');
		} else {
			$users = [$userId];
		}

		foreach ($users as $user) {
			if ($user === null) {
				continue;
			}

			$runner->setInfo('userId', $user->getUID());

			try {
				$this->collectionService->initCollectionIndexes(
					$provider,
					$collection,
					$user->getUID(),
					$options
				);
			} catch (Exception $e) {
				continue;
			}
		}
	}
}
