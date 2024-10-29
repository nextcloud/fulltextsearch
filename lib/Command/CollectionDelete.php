<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;


use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Service\CollectionService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CollectionDelete extends Base {


	/** @var CollectionService */
	private $collectionService;


	/**
	 * @param CollectionService $collectionService
	 */
	public function __construct(CollectionService $collectionService) {
		parent::__construct();

		$this->collectionService = $collectionService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:collection:delete')
			 ->setDescription('Delete collection')
			 ->addArgument('name', InputArgument::REQUIRED, 'name of the collection to delete');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$collection = $input->getArgument('name');
		if (!$this->collectionService->hasCollection($collection)) {
			throw new CollectionArgumentException('unknown collection');
		}

		$this->collectionService->deleteCollection($collection);

		return 0;
	}
}



