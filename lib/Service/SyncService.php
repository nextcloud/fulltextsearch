<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use NCU\FullTextSearch\IContentProvider;
use NCU\FullTextSearch\IManager as IFullTextSearchManager;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Db\SyncMapper;
use OCA\FullTextSearch\Enum\SessionType;
use OCA\FullTextSearch\Model\DocumentSync;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class SyncService {
	public const SYNC_LEVEL_NONE = 0;
	public const SYNC_LEVEL_CHECKSUM = 3;


	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IFullTextSearchManager $manager,
		private readonly SyncMapper $mapper,
		private readonly ProcessService $processService,
		private readonly IndexService $indexService,
		private readonly ProviderService $providerService,
		private readonly LoggerService $logger,
		private readonly LoggerInterface $coreLogger,
	) {
	}

	/**
	 * initiate one sync session from the list below and returns TRUE
	 * if a sync session did not index any document, the next entry from the list is initiated
	 * once all entries are done, method returns FALSE
	 *
	 * - sync forced index (indexed=0),
	 * - sync provider using IFTSSyncIndex,
	 * - sync document indexed previously to enabled_since and less than 10 times the time between NOW and ENABLED_SINCE
	 * - sync document indexed previously to enabled_since and more than 6 months ago,
	 * - sync document indexed previously to enabled_since and more than 3 months ago,
	 * - sync document indexed previously to enabled_since and more than 4 weeks ago,
	 * - sync document indexed previously to enabled_since and more than 2 weeks ago,
	 * - sync document indexed previously to enabled_since and more than 1 weeks ago,
	 * - sync document indexed previously to enabled_since.
	 */
	public function smartSync(): bool {
		// TODO: test link with ES
		$result = $this->syncRequestedIndexes()
				  || $this->syncContentProviders()
				  || $this->resyncRecentDocuments()
				  || $this->syncOlderDocuments(6 * 30 * 24 * 3600)
				  || $this->syncOlderDocuments(3 * 30 * 24 * 3600)
				  || $this->syncOlderDocuments(4 * 7 * 24 * 3600)
				  || $this->syncOlderDocuments(2 * 7 * 24 * 3600)
				  || $this->syncOlderDocuments(7 * 24 * 3600)
				  || $this->syncOlderDocuments();

		// ending session
		$this->logger->session();

		return $result;
	}

	/**
	 * index documents that have been set as out-of-sync
	 *
	 * @return bool FALSE if no out-of-sync documents were found
	 */
	public function syncRequestedIndexes(int $limit = 100): bool {
		$this->logger->session(SessionType::REQUESTED);
		$syncDocuments = $this->mapper->getRequestedSyncs($limit);
		if (empty($syncDocuments)) {
			return false;
		}
		$this->syncDocuments($syncDocuments);
		return true;
	}

	/**
	 * request provider for possible unknown documents to be indexed
	 *
	 * @return bool FALSE if none were found
	 */
	public function syncContentProviders(): bool {
		$this->logger->session(SessionType::SYNC);
		foreach ($this->manager->getContentProviders() as $appId => $provider) {
			$this->syncContentProvider($provider);
		}

		return false;
	}

	public function syncContentProvider(string $providerId): void {
		//$this->logger->action('providerId: ' . $providerId);
	}

	private function resyncRecentDocuments(): bool {
		$this->logger->session(SessionType::REQUESTED);
		return false;
	}

	private function syncOlderDocuments($olderThan = 0): bool {
		return false;
	}

	public function syncDocuments(array $syncDocuments): void {
		foreach ($syncDocuments as $sync) {
			$this->processService->forkIndexDocument($sync, [$this, 'syncDocument']);
		}
		$this->processService->waitForChild();
	}

	public function syncDocument(DocumentSync $sync): void {
		$time = time();
		$this->logger->action('indexing document ' . $sync->definition());
		$this->indexDocumentSync($sync);
		$sync->setIndexed($time);

//		$this->mapper->insertOrUpdate($sync);
	}

	private function indexDocumentSync(DocumentSync $sync): void {
		$provider = $this->getContentProvider($sync->getProviderId());
		if ($provider === null) {
			$this->logger->error('provider ' . $sync->getProviderId() . ' not found');
			return;
		}

		$document = $provider->getDocument($sync->getDocumentId());
		if ($document === null) {
			$this->logger->info('document ' . $sync->definition() . ' not found');
			return;
		}

		$document->setDocumentId($sync->getProviderId() . ':' . $sync->getDocumentId());
		$checksum = $document->getChecksum();
		if (($sync->getChecksum() === $checksum) && ($this->appConfig->getAppValueString(ConfigLexicon::SYNC_REQUIREMENT_LEVEL) >= self::SYNC_LEVEL_CHECKSUM)) {
			$this->logger->logger('document ' . $sync->definition() . ' seems to be identical to the indexed version');
			return;
		}

		$sync->setChecksum($checksum);

		echo json_encode($document->getFlags()) . '  ' . json_encode($document->getContent()) . "\n";
		$this->indexService->syncDocument($sync, $document);

		// send document to ES
		// confirm it is done
		sleep(rand(1, 15));
	}

	private function getContentProvider(string $providerId): ?IContentProvider {
		foreach ($this->manager->getContentProviders() as $providerClass) {
			try {
				$provider = Server::get($providerClass);
				if ($provider->getId() === $providerId) {
					return $provider;
				}
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
				$this->coreLogger->warning('could not load ' . $providerClass, ['exception' => $e]);
			}
		}

		return null;
	}

}
