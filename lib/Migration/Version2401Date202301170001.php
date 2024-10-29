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

class Version2401Date202301170001 extends SimpleMigrationStep {


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
		$expr = $qb->expr();
		$orX = $expr->orX();
		$orX->add($expr->eq('collection', $qb->createNamedParameter('')));
		$orX->add($expr->isNull('collection'));

		$qb->update('fulltextsearch_index')
		   ->set('collection', $qb->createNamedParameter('local'))
		   ->where($orX);
		$qb->executeStatement();
	}

}
