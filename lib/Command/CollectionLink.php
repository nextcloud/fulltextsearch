<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OC\Core\Command\Base;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Service\CollectionService;
use OCA\FullTextSearch\Service\ConfigService;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CollectionLink extends Base {
	public function __construct(
		private CollectionService $collectionService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:collection:link')
			->setDescription('Link collection to a user')
		    ->addArgument('collection', InputArgument::OPTIONAL, 'collection' , '')
		    ->addArgument('userId', InputArgument::OPTIONAL, 'user to link a collection to', '')
			->addOption('unlink', '', InputOption::VALUE_NONE, 'unlink collection');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws CollectionArgumentException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$links = $this->collectionService->getLinks();
		$collection = $input->getArgument('collection');

		if ($collection === '') {
			if (empty($links)) {
				$output->writeln('no collection linked to any user');
			}

			foreach($links as $name => $userId) {
				$output->writeln('- Collection <info>' . $name . '</info> linked to user <info>' . $userId . '</info>');
			}

			return 0;
		}

		if (!$this->collectionService->hasCollection($collection)) {
			throw new CollectionArgumentException('unknown collection');
		}

		if ($input->getOption('unlink')) {
			$this->collectionService->removeLink($collection);
			$output->writeln('unlinked collection');
			return 0;
		}

		$userId = $input->getArgument('userId');
		if ($userId === '') {
			throw new CollectionArgumentException('missing userId');
		}

		$this->collectionService->addLink($collection, $userId);
		$output->writeln('linked collection');

		return 0;
	}
}
