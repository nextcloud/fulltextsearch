<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use NCU\FullTextSearch\IService;
use OCA\FullTextSearch\Db\SyncMapper;
use OCA\FullTextSearch\Model\DocumentSync;
use NCU\FullTextSearch\ILoggerService;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;

class FullTextSearchService implements IService {
	public function __construct(
		private readonly SyncMapper $mapper,
		private readonly LoggerInterface $logger,
		private readonly LoggerService $loggerService,
	) {
	}

	public function getLogger(): ILoggerService {
		return $this->loggerService;
	}

	public function requestIndex(string $providerId, string $documentId): void {
		$index = new DocumentSync();
		$index->setProviderId($providerId);
		$index->setDocumentId($documentId);
		$index->setIndexed(0);
		$index->setChecksum('');

		try {
			$this->mapper->insertOrUpdate($index);
		} catch (Exception $e) {
			$this->logger->warning('could not store index', ['exception' => $e]);
		}
	}

	public function deleteIndex(string $providerId, string $documentId): void {
	}
}
