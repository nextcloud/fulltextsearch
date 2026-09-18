<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\FullTextSearch\Model\IIndexDocument;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentProvider extends Base {
	public function __construct(
		private ProviderService $providerService,
		private IndexService $indexService,
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:document:provider')
			->setDescription('Get document from index')
			->addArgument('userId', InputArgument::REQUIRED, 'userId')
			->addArgument('providerId', InputArgument::REQUIRED, 'providerId')
			->addArgument('documentId', InputArgument::REQUIRED, 'documentId')
			->addOption('content', 'c', InputOption::VALUE_NONE, 'return some content')
			->addOption('collection', null, InputOption::VALUE_OPTIONAL, 'collection to look the existing index up in, defaults to the internal collection');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
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

		$index->setOwnerId($indexDocument->getAccess()->getOwnerId());

		if (!$indexDocument->hasIndex()) {
			$indexDocument->setIndex($index);
		}

		if ($indexDocument->getIndex()
			->isStatus(Index::INDEX_REMOVE)) {
			throw new Exception('Unknown document');
		}

		$output->writeln('Document: ');
		$output->writeln(json_encode($indexDocument, JSON_PRETTY_PRINT));

		if ($input->getOption('content') !== true) {
			return 0;
		}

		$output->writeln('Content: ');
		$content = $indexDocument->getContent();
		if ($indexDocument->isContentEncoded() === IIndexDocument::ENCODED_BASE64) {
			$content = base64_decode($content, true);
		}

		$output->writeln(substr($content, 0, 80));

		$parts = $indexDocument->getParts();
		$output->writeln(sizeof($parts) . ' Part(s)');
		foreach (array_keys($parts) as $part) {
			$output->writeln(
				"'" . $part . "' " . substr($parts[$part], 0, 80) . '   (size: ' . strlen(
					$parts[$part]
				) . ')'
			);
		}

		return 0;
	}


}
