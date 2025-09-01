<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Migration;

use Closure;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version2400Date202201301329 extends SimpleMigrationStep {


	/** @var IDBConnection */
	private $dbConnection;

	/** @var ConfigService */
	private $configService;


	/**
	 * @param IDBConnection $dbConnection
	 * @param ConfigService $configService
	 */
	public function __construct(IDBConnection $dbConnection, ConfigService $configService) {
		$this->dbConnection = $dbConnection;
		$this->configService = $configService;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('fulltextsearch_index')) {
			$table = $schema->createTable('fulltextsearch_index');
			$table->addColumn(
				'provider_id', 'string',
				[
					'notnull' => true,
					'length' => 254,
				]
			);
			$table->addColumn(
				'document_id', 'string',
				[
					'notnull' => true,
					'length' => 254,
				]
			);
			$table->addColumn(
				'collection', 'string',
				[
					'default' => '',
					'notnull' => false,
					'length' => 31
				]
			);
			$table->addColumn(
				'source', 'string',
				[
					'notnull' => false,
					'length' => 64,
				]
			);
			$table->addColumn(
				'owner_id', 'string',
				[
					'notnull' => true,
					'length' => 64,
				]
			);
			$table->addColumn(
				'status', 'smallint',
				[
					'notnull' => true,
					'length' => 1,
				]
			);
			$table->addColumn(
				'options', 'string',
				[
					'notnull' => false,
					'length' => 511,
				]
			);
			$table->addColumn(
				'err', 'smallint',
				[
					'notnull' => true,
					'length' => 1,
				]
			);
			$table->addColumn(
				'message', 'text',
				[
					'notnull' => false,
				]
			);
			$table->addColumn(
				'indexed', 'bigint',
				[
					'notnull' => false,
					'length' => 6,
					'unsigned' => true
				]
			);
			$table->setPrimaryKey(['provider_id', 'document_id', 'collection']);
			$table->addIndex(['collection']);
			$table->addIndex(['collection', 'provider_id', 'document_id', 'status'], 'cpds');
		}

		return $schema;
	}
}
