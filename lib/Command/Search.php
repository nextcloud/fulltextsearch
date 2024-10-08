<?php

declare(strict_types=1);
/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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



