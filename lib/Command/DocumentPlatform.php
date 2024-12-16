<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Service\PlatformService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentPlatform extends Base {
	public function __construct(
		private PlatformService $platformService
	) {
		parent::__construct();
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



