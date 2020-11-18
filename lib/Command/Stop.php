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
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\RunningService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class Stop
 *
 * @package OCA\FullTextSearch\Command
 */
class Stop extends Base {


	/** @var RunningService */
	private $runningService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param RunningService $runningService
	 * @param MiscService $miscService
	 */
	public function __construct(RunningService $runningService, MiscService $miscService) {
		parent::__construct();
		$this->runningService = $runningService;

		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:stop')
			 ->setDescription('Stop all indexing');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('stopping all running indexes');

		$this->runningService->forceStop();

		return 0;
	}


}



