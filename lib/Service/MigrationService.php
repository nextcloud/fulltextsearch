<?php

declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\FullTextSearch\Service;

use OCP\DB\Exception;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Console\Helper\ProgressBar;

class MigrationService {


	/** @var IDBConnection */
	private $dbConnection;

	/** @var ConfigService */
	private $configService;


	/** @var ProgressBar */
	private $progressBar;


	/**
	 * @param IDBConnection $dbConnection
	 * @param ConfigService $configService
	 */
	public function __construct(
		IDBConnection $dbConnection,
		ConfigService $configService
	) {
		$this->dbConnection = $dbConnection;
		$this->configService = $configService;
	}


	public function setProgressBar(ProgressBar $progressBar): void {
		$this->progressBar = $progressBar;
	}


	/**
	 * @param int $size
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function migrate24Chunk(int $size = 10000): bool {
		if ($this->configService->getAppValueInt(ConfigService::MIGRATION_24) === 1) {
			return false;
		}

		if ($size === 0) {
			return false;
		}

		$oldData = $this->getChunkOldData($size);
		if ($oldData->rowCount() === 0) {
			$this->configService->setAppValue(ConfigService::MIGRATION_24, '1');
			if ($this->dbConnection->tableExists('fulltextsearch_indexes')) {
				$this->dbConnection->dropTable('fulltextsearch_indexes');
			}

			return false;
		}

		$this->createNewEntries($oldData);

		return true;
	}


	/**
	 * @param int $size
	 *
	 * @return IResult
	 * @throws Exception
	 */
	private function getChunkOldData(int $size): IResult {
		$query = $this->dbConnection->getQueryBuilder();
		$query->select('*')
			  ->from('fulltextsearch_indexes')
			  ->orderBy('provider_id', 'asc')
			  ->addOrderBy('document_id', 'asc')
			  ->setFirstResult(0)
			  ->setMaxResults($size);

		return $query->executeQuery();
	}


	/**
	 * @param IResult $oldData
	 *
	 * @throws Exception
	 */
	private function createNewEntries(IResult $oldData): void {
		$create = $this->dbConnection->getQueryBuilder();
		$create->insert('fulltextsearch_index')
			   ->values(
			   	[
			   		'owner_id' => $create->createParameter('owner_id'),
			   		'provider_id' => $create->createParameter('provider_id'),
			   		'collection' => $create->createParameter('collection'),
			   		'document_id' => $create->createParameter('document_id'),
			   		'source' => $create->createParameter('source'),
			   		'err' => $create->createParameter('err'),
			   		'message' => $create->createParameter('message'),
			   		'status' => $create->createParameter('status'),
			   		'options' => $create->createParameter('options'),
			   		'indexed' => $create->createParameter('indexed')
			   	]
			   );

		while ($row = $oldData->fetch()) {
			$create->setParameter('owner_id', $row['owner_id'])
				   ->setParameter('provider_id', $row['provider_id'])
				   ->setParameter('collection', '')
				   ->setParameter('document_id', $row['document_id'])
				   ->setParameter('source', $row['source'])
				   ->setParameter('err', $row['err'], IQueryBuilder::PARAM_INT)
				   ->setParameter('message', $row['message'])
				   ->setParameter('status', $row['status'], IQueryBuilder::PARAM_INT)
				   ->setParameter('options', $row['options'])
				   ->setParameter('indexed', $row['indexed'], IQueryBuilder::PARAM_INT);

			if (!is_null($this->progressBar)) {
				$this->progressBar->advance();
			}

			try {
				$create->executeStatement();
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $e;
				}
			}

			$delete = $this->dbConnection->getQueryBuilder();
			$delete->delete('fulltextsearch_indexes');
			$delete->where(
				$delete->expr()->eq('provider_id', $delete->createNamedParameter($row['provider_id']))
			);
			$delete->andWhere(
				$delete->expr()->eq('document_id', $delete->createNamedParameter($row['document_id']))
			);

			$delete->executeStatement();
		}
	}
}
