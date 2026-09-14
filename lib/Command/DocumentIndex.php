<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:document:index',
	description: 'index one specific document',
)]
class DocumentIndex {
	public function __construct(
		private ProviderService $providerService,
		private PlatformService $platformService,
		private IndexService $indexService,
	) {
	}

	/**
	 * @throws Exception
	 */
	public function __invoke(
		IOutput $output,
		#[Argument(description: 'userId')]
		string $userId,
		#[Argument(description: 'providerId')]
		string $providerId,
		#[Argument(description: 'documentId')]
		string $documentId,
		#[Option(description: 'collection to look the existing index up in, defaults to the internal collection')]
		string $collection = '',
	): ExitCode {
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

		return ExitCode::Success;
	}


}
