<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Migration;

use Closure;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2801Date202309200001 extends SimpleMigrationStep {

	/** @var IDBConnection */
	private $dbConnection;

	/**
	 * @param IDBConnection $dbConnection
	 */
	public function __construct(IDBConnection $dbConnection) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @throws Exception
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('fulltextsearch_index')) {
			return;
		}

		$qb = $this->dbConnection->getQueryBuilder();
		try {
			$qb->update('fulltextsearch_index')
				->set('collection', $qb->createNamedParameter('local'))
				->where(
					$qb->expr()->orX(
						$qb->expr()->eq('collection', $qb->createNamedParameter('')),
						$qb->expr()->isNull('collection')
					)
				);
			$qb->executeStatement();
		} catch (\Exception $e) {

			// in case on failure, because of deprecated/duplicate entry,
			// we update row per row and delete those duplicate
			$select = $this->dbConnection->getQueryBuilder();
			$select->select('provider_id', 'document_id', 'collection')
				->from('fulltextsearch_index')
				->where(
					$select->expr()->orX(
						$select->expr()->eq('collection', $select->createNamedParameter('')),
						$select->expr()->isNull('collection')
					)
				);

			$result = $select->executeQuery();
			while ($data = $result->fetch()) {
				$update = $this->dbConnection->getQueryBuilder();

				$update->update('fulltextsearch_index')
					->set('collection', $update->createNamedParameter('local'))
					->where($update->expr()->andX(
						$update->expr()->eq('provider_id', $update->createNamedParameter($data['provider_id'])),
						$update->expr()->eq('document_id', $update->createNamedParameter($data['document_id'])),
						$update->expr()->eq('collection', $update->createNamedParameter($data['collection'])),
					));

				try {
					$update->executeStatement();
				} catch (\Exception $e) {
					$delete = $this->dbConnection->getQueryBuilder();
					$delete->delete('fulltextsearch_index')->where($delete->expr()->andX(
						$delete->expr()->eq('provider_id', $delete->createNamedParameter($data['provider_id'])),
						$delete->expr()->eq('document_id', $delete->createNamedParameter($data['document_id'])),
						$delete->expr()->eq('collection', $delete->createNamedParameter($data['collection'])),
					));
					$delete->executeStatement();
				}
			}
			$result->closeCursor();
		}

	}
}
