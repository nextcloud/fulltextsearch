<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Files\Service;

use NCU\FullTextSearch\Model\Document;
use NCU\FullTextSearch\Model\DocumentAccess;
use OC\User\NoUserException;
use OCA\FullTextSearch\Files\Exceptions\NodeNotFoundException;
use OCA\FullTextSearch\Service\LoggerService;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotPermittedException;
use OCP\FullTextSearch\Model\IIndexDocument;

class FilesService {
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserMountCache $userMountCache,
		private readonly SharesService $sharesService,
		private readonly LoggerService $logger,
	) {
	}

	public function getNode(int $nodeId): ?Node {
		$mounts = $this->userMountCache->getMountsForFileId($nodeId);
		if (empty($mounts ?? [])) {
			$this->logger->warning('empty mount for nodeId=' . $nodeId);
			return null;
		}

		$mount = reset($mounts);
		try {
			return $this->rootFolder->getUserFolder($mount->getUser()->getUID())->getFirstNodeById($nodeId);
		} catch (NotPermittedException|NoUserException $e) {
			$this->logger->error('could not find node', ['exception' => $e]);
		}

		return null;
	}

	public function generateDocument(Node $node): ?Document {
		if ($node->getType() !== 'file') {
			return null;
		}

		$document = new Document();
//		$document->setId((string)$node->getId());`

		$document->setFlags(8);
		$document->setContent(base64_encode($node->getContent()), true);
		$document->setAccess($this->sharesService->getDocumentAccess($node));

		return $document;
	}

}


