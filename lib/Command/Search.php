<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Service\SearchService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:search',
	description: 'Search something',
	supportsOutputFormat: true,
)]
class Search {
	public function __construct(
		private SearchService $searchService,
	) {
	}


	/**
	 * @throws Exception
	 */
	public function __invoke(
		IOutput $output,
		#[Argument(description: 'user')]
		string $user = '',
		#[Argument(description: 'needle')]
		string $string = '',
	): ExitCode {
		$searchRequest = new SearchRequest();
		$searchRequest->importFromArray(
			[
				'providers' => 'all',
				'search' => $string
			]
		);

		$searchResult = $this->searchService->search($user, $searchRequest);

		$results = [];
		foreach ($searchResult as $entry) {
			$list = [];
			foreach ($entry->getDocuments() as $document) {
				$list[] = json_decode(json_encode($document, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
			}

			$results[$entry->getProvider()->getId()] = array_values($list);
		}

		$output->writeArrayInOutputFormat($results, ' * ');

		return ExitCode::Success;
	}
}
