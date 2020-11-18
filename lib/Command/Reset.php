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
use OC\Core\Command\InterruptedException;
use OCA\FullTextSearch\ACommandBase;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\RunningService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Reset
 *
 * @package OCA\FullTextSearch\Command
 */
class Reset extends ACommandBase {


	/** @var IndexService */
	private $indexService;

	/** @var MiscService */
	private $miscService;

	/** @var Runner */
	private $runner;


	/**
	 * Index constructor.
	 *
	 * @param RunningService $runningService
	 * @param IndexService $indexService
	 * @param MiscService $miscService
	 */
	public function __construct(
		RunningService $runningService, IndexService $indexService, MiscService $miscService
	) {
		parent::__construct();
		$this->indexService = $indexService;

		$this->runner = new Runner($runningService, 'commandReset');
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:reset')
			 ->setDescription('Reset index')
			 ->addArgument('provider', InputArgument::OPTIONAL, 'provider');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		try {
			$this->runner->sourceIsCommandLine($this, $output);
			$this->runner->start();
			$this->runner->output('reset.');

		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

		$this->indexService->setRunner($this->runner);
		try {
			$this->indexService->resetIndex($this->getProviderIdFromArgument($input));

		} catch (Exception $e) {
			throw $e;
		} finally {
			$this->runner->stop();
		}

		return 0;
	}


	/**
	 * @param InputInterface $input
	 *
	 * @return string
	 */
	private function getProviderIdFromArgument(InputInterface $input): string {
		$providerId = $input->getArgument('provider');
		if ($providerId === null) {
			$providerId = '';
		}

		return $providerId;
	}


	/**
	 * @throws TickDoesNotExistException
	 */
	public function abort() {
		try {
			$this->abortIfInterrupted();
		} catch (InterruptedException $e) {
			$this->runner->stop();
			exit();
		}
	}

}



