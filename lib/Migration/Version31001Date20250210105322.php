<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddIndex('fulltextsearch_index', IndexType::INDEX, 'fix live index select')]
class Version31001Date20250210105322 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('fulltextsearch_index')) {
			return null;
		}

		$table = $schema->getTable('fulltextsearch_index');
		if ($table->hasIndex('fts_ces')) {
			return null;
		}

		$table->addIndex(['collection', 'err', 'status'], 'fts_ces');

		return $schema;
	}
}
