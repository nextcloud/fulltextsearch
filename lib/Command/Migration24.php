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
