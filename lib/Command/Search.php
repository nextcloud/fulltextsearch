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
use OCA\FullTextSearch\Model\SearchResult;
use OCA\FullTextSearch\Service\SearchService;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\ISearchResult;
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
		$output->writeln('search');

		$searchRequest = new SearchRequest();
		$searchRequest->importFromArray(
			[
				'providers' => 'all',
				'search'    => $input->getArgument('string')
			]
		);

		try {
			$result = $this->searchService->search($input->getArgument('user'), $searchRequest);

			foreach ($result as $searchResult) {
				$this->displaySearchResult($searchResult);
			}

		} catch (Exception $e) {
			throw $e;
		}

		return 0;
	}


	/**
	 * @param ISearchResult $searchResult
	 */
	private function displaySearchResult(ISearchResult $searchResult) {
		/** @var SearchResult $searchResult */
		echo '> ' . $searchResult->getProvider()
								 ->getName() . "\n";

		/** @var IIndexDocument[] $result */
		$result = $searchResult->getDocuments();
		foreach ($result as $document) {
			echo ' - ' . $document->getId() . ' score:' . $document->getScore() . "\n";
		}
	}


}



