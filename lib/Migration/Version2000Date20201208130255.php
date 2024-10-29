<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version2000Date20201208130255 extends SimpleMigrationStep {

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

		if (!$schema->hasTable('fulltextsearch_ticks')) {
			$table = $schema->createTable('fulltextsearch_ticks');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 7,
				'unsigned' => true,
			]);
			$table->addColumn('source', 'string', [
				'notnull' => false,
				'length' => 128,
			]);
			$table->addColumn('data', Types::TEXT, [
				'notnull' => false
			]);
			$table->addColumn('status', 'string', [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('action', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('first_tick', 'bigint', [
				'notnull' => false,
				'length' => 6,
			]);
			$table->addColumn('tick', 'bigint', [
				'notnull' => false,
				'length' => 6,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
