<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Db;

use OCA\FullTextSearch\Model\DocumentSync;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<DocumentSync>
 */
class SyncMapper extends QBMapper {
	public const TABLE = 'fulltextsearch_sync';

	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, self::TABLE, DocumentSync::class);
	}

	/**
	 * @return DocumentSync[]
	 */
	public function getRequestedSyncs(int $limit = 100): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('indexed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
		   ->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	public function update(Entity $entity): Entity {
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('indexed', $qb->createNamedParameter($entity->getIndexed(), IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('provider_id', $qb->createNamedParameter($entity->getProviderId())),
				$qb->expr()->eq('document_id', $qb->createNamedParameter($entity->getDocumentId())),
			);
		$qb->executeStatement();
		return $entity;
	}

//	public function reset(string $providerId, string $documentId): void {
//		$qb = $this->db->getQueryBuilder();
//		$qb->update($this->getTableName())
//		   ->set('indexed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
//		   ->where(
//			   $qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId())),
//			   $qb->expr()->eq('document_id', $qb->createNamedParameter($documentId())),
//		   );
//		$qb->executeStatement();
//	}
}
