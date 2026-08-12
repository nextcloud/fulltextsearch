<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Service\CollectionService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;

#[AsCommand(
	name: 'fulltextsearch:collection:delete',
	description: 'Delete collection',
)]
class CollectionDelete {


	/** @var CollectionService */
	private $collectionService;


	/**
	 * @param CollectionService $collectionService
	 */
	public function __construct(CollectionService $collectionService) {
		$this->collectionService = $collectionService;
	}


	/**
	 * @throws CollectionArgumentException
	 */
	public function __invoke(
		#[Argument(description: 'name of the collection to delete')]
		string $name,
	): ExitCode {
		if (!$this->collectionService->hasCollection($name)) {
			throw new CollectionArgumentException('unknown collection');
		}

		$this->collectionService->deleteCollection($name);

		return ExitCode::Success;
	}
}
