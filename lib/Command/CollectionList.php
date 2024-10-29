<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OC\Core\Command\Base;
use OCA\FullTextSearch\Service\CollectionService;
use OCA\FullTextSearch\Service\ConfigService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionList extends Base {
	public function __construct(
		private CollectionService $collectionService,
		private ConfigService $configService
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:collection:list')
			->setDescription('List collections');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$collections = $this->collectionService->getCollections();
		$output->writeln('found ' . sizeof($collections) . ' collection(s)');

		foreach ($this->collectionService->getCollections() as $collection) {
			$output->writeln('- ' . (($collection === $this->configService->getInternalCollection()) ? '*' : '') . $collection);
		}

		return 0;
	}
}
