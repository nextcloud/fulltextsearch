<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;


use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\DatabaseException;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MigrationService;
use OCP\DB\Exception;
use OCP\IDBConnection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Migration24 extends Base {


	/** @var IDBConnection */
	private $dbConnection;

	/** @var MigrationService */
	private $migrationService;

	/** @var ConfigService */
	private $configService;


	/**
	 * @param IDBConnection $dbConnection
	 * @param MigrationService $migrationService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IDBConnection $dbConnection,
		MigrationService $migrationService,
		ConfigService $configService
	) {
		parent::__construct();

		$this->dbConnection = $dbConnection;
		$this->migrationService = $migrationService;
		$this->configService = $configService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:migration:24')
			 ->setDescription('Migrate index for NC24');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->configService->requireMigration24();
			throw new \Exception('Should not be required');
		} catch (DatabaseException $e) {
		}

		$this->runMigration($output);

		return 0;
	}


	private function runMigration(OutputInterface $output): void {
		$progressBar = new ProgressBar($output, $this->getTotalRows());
		$this->migrationService->setProgressBar($progressBar);
		while (true) {
			if (!$this->migrationService->migrate24Chunk(10000, $progressBar)) {
				break;
			}
		}

		$progressBar->finish();
		$output->writeln('');
	}


	/**
	 * @return int
	 * @throws Exception
	 */
	private function getTotalRows(): int {
		$query = $this->dbConnection->getQueryBuilder();
		$query->select($query->func()->count('*', 'index_count'))
			  ->from('fulltextsearch_indexes');
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		return (int)$row['index_count'];
	}

}
