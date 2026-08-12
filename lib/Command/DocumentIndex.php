<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;

#[AsCommand(
	name: 'fulltextsearch:document:index',
	description: 'index one specific document',
)]
class DocumentIndex {
	public function __construct(
		private ProviderService $providerService,
		private PlatformService $platformService,
	) {
	}

	/**
	 * @throws Exception
	 */
	public function __invoke(
		#[Argument(description: 'userId')]
		string $userId,
		#[Argument(description: 'providerId')]
		string $providerId,
		#[Argument(description: 'documentId')]
		string $documentId,
	): ExitCode {
		$providerWrapper = $this->providerService->getProvider($providerId);
		$provider = $providerWrapper->getProvider();

		$index = new Index($providerId, $documentId);
		$index->setOwnerId($userId);
		$index->setStatus(Index::INDEX_FULL);
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
