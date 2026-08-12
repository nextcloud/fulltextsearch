<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Service\CollectionService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:collection:link',
	description: 'Link collection to a user',
)]
class CollectionLink {
	public function __construct(
		private CollectionService $collectionService,
	) {
	}

	/**
	 * @throws CollectionArgumentException
	 */
	public function __invoke(
		IOutput $output,
		#[Argument(description: 'collection')]
		string $collection = '',
		#[Argument(description: 'user to link a collection to')]
		string $userId = '',
		#[Option(description: 'unlink collection')]
		bool $unlink = false,
	): ExitCode {
		$links = $this->collectionService->getLinks();

		if ($collection === '') {
			if (empty($links)) {
				$output->writeln('no collection linked to any user');
			}

			foreach ($links as $name => $linkedUserId) {
				$output->writeln('- Collection <info>' . $name . '</info> linked to user <info>' . $linkedUserId . '</info>');
			}

			return ExitCode::Success;
		}

		if (!$this->collectionService->hasCollection($collection)) {
			throw new CollectionArgumentException('unknown collection');
		}

		if ($unlink) {
			$this->collectionService->removeLink($collection);
			$output->writeln('unlinked collection');

			return ExitCode::Success;
		}

		if ($userId === '') {
			throw new CollectionArgumentException('missing userId');
		}

		$this->collectionService->addLink($collection, $userId);
		$output->writeln('linked collection');

		return ExitCode::Success;
	}
}
