<?php
/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\FullNextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullNextSearch\Model\SearchResult;
use OCA\FullNextSearch\Service\MiscService;
use OCA\FullNextSearch\Service\SearchService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Search extends Base {

	/** @var SearchService */
	private $searchService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param SearchService $searchService
	 * @param MiscService $miscService
	 *
	 * @internal param IndexService $indexService
	 */
	public function __construct(SearchService $searchService, MiscService $miscService) {
		parent::__construct();
		$this->searchService = $searchService;

		$this->miscService = $miscService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('fullnextsearch:search')
			 ->setDescription('Search something')
			 ->addArgument('user', InputArgument::OPTIONAL, 'user')
			 ->addArgument('string', InputArgument::OPTIONAL, 'needle');

	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('search');

		try {
			$result = $this->searchService->search(
				'files', $input->getArgument('user'), $input->getArgument('string')
			);

			foreach ($result as $searchResult) {
				$this->displaySearchResult($searchResult);
			}

		} catch (Exception $e) {
			throw $e;
		}
	}


	private function displaySearchResult(SearchResult $searchResult) {

		echo '> ' . $searchResult->getProvider()
								 ->getName() . "\n";
		foreach ($searchResult->getDocuments() as $document) {
			echo ' - ' . $document->getId() . ' score:' . $document->getScore() . "\n";
		}
	}


}



