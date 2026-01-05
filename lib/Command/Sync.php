<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\LockException;
use OCA\FullTextSearch\Service\FullTextSearchService;
use OCA\FullTextSearch\Service\LockService;
use OCA\FullTextSearch\Service\LoggerService;
use OCA\FullTextSearch\Service\SyncService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends Base {
	public function __construct(
		private readonly LockService $lockService,
		private readonly SyncService $syncService,
		private readonly FullTextSearchService $fullTextSearchService,
		private readonly LoggerService $loggerService,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:sync')
			 ->setDescription('Index files')
			 ->addOption('info', '', InputOption::VALUE_NONE, 'display info entries')
			 ->addOption('no-output', '', InputOption::VALUE_NONE, 'no output, use nextcloud logs');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!$input->getOption('no-output')) {
			$this->loggerService->setOutputInterface($output, $input->getOption('info'));
		}
//		$this->fullTextSearchService->requestIndex('files', '123');
		$this->lockService->lock();

		while (true) {
			try {
				$this->lockService->update();
				$this->syncProcess();
			} catch (LockException $e) {
				throw $e;
			} catch (Exception $e) {
				$this->loggerService->error('Exception while running fulltextsearch:sync', ['exception' => $e]);
			}
			sleep(15);
		}

		return 0;
	}

	private function syncProcess() {
		$this->loggerService->info('initiating a new sync session');
		$this->syncService->smartSync();
		$this->loggerService->info('sync session closed');
	}
}

