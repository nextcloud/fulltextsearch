<?php

declare(strict_types=1);

namespace OCA\FullTextSearch\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
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
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('fulltextsearch_indexes')) {
			$table = $schema->createTable('fulltextsearch_indexes');
			$table->addColumn('provider_id', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('document_id', 'string', [
				'notnull' => true,
				'length' => 254,
			]);
			$table->addColumn('source', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('owner_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('status', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('options', 'string', [
				'notnull' => false,
				'length' => 511,
			]);
			$table->addColumn('err', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('message', 'string', [
				'notnull' => false,
				'length' => 8000,
			]);
			$table->addColumn('indexed', 'bigint', [
				'notnull' => false,
				'length' => 6,
			]);
			$table->setPrimaryKey(['provider_id', 'document_id']);
		}

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
			$table->addColumn('data', 'string', [
				'notnull' => false,
				'length' => 6000,
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
