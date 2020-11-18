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
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DocumentPlatform extends Base {


	/** @var PlatformService */
	private $platformService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param PlatformService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(PlatformService $providerService, MiscService $miscService) {
		parent::__construct();

		$this->platformService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:document:platform')
			 ->setDescription('Get document from index')
			 ->addArgument('providerId', InputArgument::REQUIRED, 'providerId')
			 ->addArgument('documentId', InputArgument::REQUIRED, 'documentId')
			 ->addOption('content', 'c', InputOption::VALUE_NONE, 'return some content');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$providerId = $input->getArgument('providerId');
		$documentId = $input->getArgument('documentId');

		$wrapper = $this->platformService->getPlatform();
		$platform = $wrapper->getPlatform();

		$indexDocument = $platform->getDocument($providerId, $documentId);
		$result = [
			'document' => $indexDocument
		];
		if ($input->getOption('content') === true) {
			$result['content'] = substr($indexDocument->getContent(), 0, 200);
		}

		$output->writeln(json_encode($result, JSON_PRETTY_PRINT));

		return 0;
	}


}



