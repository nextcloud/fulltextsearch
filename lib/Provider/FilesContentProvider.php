<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Provider;

use Generator;
use NCU\FullTextSearch\Model\Document;
use OCA\FullTextSearch\Files\Service\FilesService;
use OCA\FullTextSearch\Files\Service\SharesService;
use NCU\FullTextSearch\IContentProvider;
use NCU\FullTextSearch\IContentProviderImprovedSearch;
use NCU\FullTextSearch\IContentProviderSyncIndex;
use NCU\FullTextSearch\IIndexQueryHelper;
use NCU\FullTextSearch\Model\UnindexedDocument;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\FullTextSearch\Model\ISearchTemplate;
use OCP\IDBConnection;

class FilesContentProvider implements
	IContentProvider,
	IContentProviderSyncIndex,
	IContentProviderImprovedSearch {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly FilesService $filesService,
		private readonly SharesService $sharesService,
	) {
	}

	public function getId(): string {
		return 'files';
	}

	public function getConfiguration(): array {
		return [];
	}

	public function getDocument(string $documentId): ?Document {
		$nodeId = (int)$documentId;
		$node = $this->filesService->getNode($nodeId);
		if ($node === null) {
			return null;
		}

		return $this->filesService->generateDocument($node);
	}

	// IContentProviderImprovedSearch
	public function getSearchTemplate(): ?ISearchTemplate {
		return null;
	}

	// IContentProviderImprovedSearch
	public function improveSearchRequest(ISearchRequest $searchRequest): void {
	}

	// IContentProviderImprovedSearch
	public function improveSearchResult(ISearchResult $searchResult): void {
	}

	// IContentProviderSyncIndex
	public function getUnindexedDocuments(IIndexQueryHelper $qh): Generator {
		$qh->notNeeded();
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid', 'mtime')
		   ->from('filecache');
		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			yield new UnindexedDocument($row['fileid'], $row['mtime']);
		}
		$result->closeCursor();
	}
}

