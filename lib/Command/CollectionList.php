<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OCA\FullTextSearch\Service\CollectionService;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:collection:list',
	description: 'List collections',
)]
class CollectionList {
	public function __construct(
		private CollectionService $collectionService,
		private ConfigService $configService,
	) {
	}

	public function __invoke(IOutput $output): ExitCode {
		$collections = $this->collectionService->getCollections();
		$output->writeln('found ' . sizeof($collections) . ' collection(s)');

		foreach ($collections as $collection) {
			$output->writeln('- ' . (($collection === $this->configService->getInternalCollection()) ? '*' : '') . $collection);
		}

		return ExitCode::Success;
	}
}
