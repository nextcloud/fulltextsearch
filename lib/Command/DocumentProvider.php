<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\FullTextSearch\Model\IIndexDocument;

#[AsCommand(
	name: 'fulltextsearch:document:provider',
	description: 'Get document from index',
)]
class DocumentProvider {
	public function __construct(
		private ProviderService $providerService,
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
		#[Option(description: 'return some content', shortcut: 'c')]
		bool $content = false,
	): ExitCode {
		$providerWrapper = $this->providerService->getProvider($providerId);
		$provider = $providerWrapper->getProvider();

		$index = new Index($providerId, $documentId);
		$index->setOwnerId($userId);
		$index->setStatus(Index::INDEX_FULL);
		try {
			$index = \OC::$server->get(\OCA\FullTextSearch\Service\IndexService::class)->getIndex($providerId, $documentId);
		} catch (\Throwable $t) {
			$output->writeln("<error>Index not found : index attribute has been set to default values</error>");
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

		if ($content !== true) {
			return ExitCode::Success;
		}

		$output->writeln('Content: ');
		$documentContent = $indexDocument->getContent();
		if ($indexDocument->isContentEncoded() === IIndexDocument::ENCODED_BASE64) {
			$documentContent = base64_decode($documentContent, true);
		}

		$output->writeln(substr($documentContent, 0, 80));

		$parts = $indexDocument->getParts();
		$output->writeln(sizeof($parts) . ' Part(s)');
		foreach (array_keys($parts) as $part) {
			$output->writeln(
				"'" . $part . "' " . substr($parts[$part], 0, 80) . '   (size: ' . strlen(
					$parts[$part]
				) . ')'
			);
		}

		return ExitCode::Success;
	}


}
