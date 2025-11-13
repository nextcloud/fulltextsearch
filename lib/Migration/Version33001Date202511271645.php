<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[CreateTable('fulltextsearch_sync', ['provider_id', 'document_id', 'indexed'], description: 'table to manage indexed documents and new index request')]
class Version33001Date202511271645 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('fulltextsearch_sync')) {
			return null;
		}

		$table = $schema->createTable('fulltextsearch_sync');
		$table->addColumn('provider_id', Types::STRING, [
			'length' => 31,
			'notnull' => true,
		]);
		$table->addColumn('document_id', Types::STRING, [
			'length' => 31,
			'notnull' => true,
		]);
		$table->addColumn('flags', Types::INTEGER, [
			'length' => 7,
			'default' => 0,
		]);
		$table->addColumn('indexed', Types::BIGINT, [
			'length' => 11,
			'default' => 0,
		]);
		$table->addColumn('checksum', Types::STRING, [
			'length' => 16,
			'notnull' => true,
		]);

		$table->setPrimaryKey(['provider_id', 'document_id'], 'fts_i_pd');
		$table->addIndex(['indexed'], 'fts_i_i');

		return $schema;
	}
}
