<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\PlatformTemporaryException;
use OCA\FullTextSearch\Service\FullTextSearchService;
use OCA\FullTextSearch\Service\LockService;
use OCA\FullTextSearch\Service\LoggerService;
use OCA\FullTextSearch\Service\SyncService;
use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use Exception;
use OC\Core\Command\InterruptedException;
use OCA\FullTextSearch\ACommandBase;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Index as ModelIndex;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\CliService;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\IUserManager;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;
use Throwable;

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
			} catch (Exception $e) {
				$this->loggerService->error('Exception while running fulltextsearch:sync', ['exception' => $e]);
			}
			sleep(10);
		}

		return 0;
	}

	private function syncProcess() {
		$this->loggerService->info('initiating a new sync session');
		$this->syncService->smartSync();
		$this->loggerService->info('sync session closed');
	}
}

