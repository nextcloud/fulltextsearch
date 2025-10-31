<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Files\Service;

use NCU\FullTextSearch\Model\DocumentAccess;
use OCA\FullTextSearch\Service\LoggerService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\IDBConnection;
use OCP\Share\IShare;

class SharesService {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly LoggerService $logger,
	) {
	}

	public function getDocumentAccess(Node $node): DocumentAccess {
		$owner = $node->getOwner()?->getUID() ?? '';
		$users = $groups = $circles = $links = [];
		foreach($this->getShares($node->getId()) as $share) {
			switch ($share['share_type']) {
				case IShare::TYPE_USER:
					$users[] = $share['share_with'];
					break;
				case IShare::TYPE_GROUP:
					$groups[] = $share['share_with'];
					break;
				case IShare::TYPE_CIRCLE:
					$circles[] = $share['share_with'];
					break;
				case IShare::TYPE_LINK:
					$links[] = $share['token'];
					break;
			}
		}

		return new DocumentAccess(
			$owner,
			$users,
			$groups,
			$circles,
			$links,
		);
	}

	private function getShares(int $nodeId): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('share_type', 'share_with', 'token')
		   ->from('share')
		   ->where(
			   $qb->expr()->eq('item_source', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_INT)),
			   $qb->expr()->isNotNull('parent')
		   );

		$shares = [];
		$cursor = $qb->executeQuery();
		while ($row = $cursor->fetch()) {
			$shares[] = [
				'share_type' => $row['share_type'],
				'share_with' => $row['share_with'],
			];
		}
		$cursor->closeCursor();

		return $shares;
	}
}

