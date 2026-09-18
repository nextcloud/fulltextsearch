<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentIndex extends Base {
	public function __construct(
		private ProviderService $providerService,
		private PlatformService $platformService,
		private IndexService $indexService,
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:document:index')
			->setDescription('index one specific document')
			->addArgument('userId', InputArgument::REQUIRED, 'userId')
			->addArgument('providerId', InputArgument::REQUIRED, 'providerId')
			->addArgument('documentId', InputArgument::REQUIRED, 'documentId')
			->addOption('collection', null, InputOption::VALUE_OPTIONAL, 'collection to look the existing index up in, defaults to the internal collection');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$providerId = $input->getArgument('providerId');
		$documentId = $input->getArgument('documentId');
		$userId = $input->getArgument('userId');
		$collection = $input->getOption('collection');

		$providerWrapper = $this->providerService->getProvider($providerId);
		$provider = $providerWrapper->getProvider();

		$index = new Index($providerId, $documentId);
		$index->setOwnerId($userId);
		$index->setStatus(Index::INDEX_FULL);
		try {
			$index = $this->indexService->getIndex($providerId, $documentId, $collection);
		} catch (IndexDoesNotExistException $e) {
			$output->writeln('<error>Index not found: index attributes have been set to default values</error>');
		}
		$indexDocument = $provider->updateDocument($index);
		if (!$indexDocument->hasIndex()) {
			$indexDocument->setIndex($index);
		}

		if ($indexDocument->getIndex()
			->isStatus(Index::INDEX_REMOVE)) {
			throw new Exception('Unknown document');
		}

		$platformWrapper = $this->platformService->getPlatform();
		$platform = $platformWrapper->getPlatform();

		$indexDocument->getIndex()
			->setStatus(Index::INDEX_FULL);
		$platform->indexDocument($indexDocument);

		return 0;
	}


}
