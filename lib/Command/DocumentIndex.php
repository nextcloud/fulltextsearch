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
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class DocumentIndex
 *
 * @package OCA\FullTextSearch\Command
 */
class DocumentIndex extends Base {


	/** @var ProviderService */
	private $providerService;

	/** @var PlatformService */
	private $platformService;

	/** @var MiscService */
	private $miscService;


	/**
	 * DocumentIndex constructor.
	 *
	 * @param ProviderService $providerService
	 * @param PlatformService $platformService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ProviderService $providerService, PlatformService $platformService, MiscService $miscService
	) {
		parent::__construct();

		$this->providerService = $providerService;
		$this->platformService = $platformService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:document:index')
			 ->setDescription('index one specific document')
			 ->addArgument('userId', InputArgument::REQUIRED, 'userId')
			 ->addArgument('providerId', InputArgument::REQUIRED, 'providerId')
			 ->addArgument('documentId', InputArgument::REQUIRED, 'documentId');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$providerId = $input->getArgument('providerId');
		$documentId = $input->getArgument('documentId');
		$userId = $input->getArgument('userId');

		$providerWrapper = $this->providerService->getProvider($providerId);
		$provider = $providerWrapper->getProvider();

		$index = new Index($providerId, $documentId);
		$index->setOwnerId($userId);
		$index->setStatus(Index::INDEX_FULL);
		$indexDocument = $provider->updateDocument($index);
		if (!$indexDocument->hasIndex()) {
			$indexDocument->setIndex($index);
		}

		if ($indexDocument->getIndex()
						  ->isStatus(Index::INDEX_REMOVE)) {
			throw new Exception('Unknown document');
		}

		$platformWrapper = $this->platformService->getPlatform();
		$platform = $platformWrapper->getPlatform();

		$indexDocument->getIndex()
					  ->setStatus(Index::INDEX_FULL);
		$platform->indexDocument($indexDocument);

		return 0;
	}


}



