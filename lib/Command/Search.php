<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Service\SearchService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Search extends Base {
	public function __construct(
		private SearchService $searchService
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:search')
			 ->setDescription('Search something')
			 ->addArgument('user', InputArgument::OPTIONAL, 'user')
			 ->addArgument('string', InputArgument::OPTIONAL, 'needle');

	}


	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$searchRequest = new SearchRequest();
		$searchRequest->importFromArray(
			[
				'providers' => 'all',
				'search' => $input->getArgument('string')
			]
		);

		$searchResult = $this->searchService->search($input->getArgument('user'), $searchRequest);

		$results = [];
		foreach ($searchResult as $entry) {
			$list = [];
			foreach ($entry->getDocuments() as $document) {
				$list[] = json_decode(json_encode($document, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
			}

			$results[$entry->getProvider()->getId()] = array_values($list);
		}

		$this->writeArrayInOutputFormat($input, $output, $results, ' * ');
		return 0;
	}
}



