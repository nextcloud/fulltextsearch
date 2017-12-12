<?php
/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */


namespace OCA\FullNextSearch\Model;

use OC\Core\Command\Base;
use OCA\FullNextSearch\Exceptions\TickIsNotAliveException;
use OCA\FullNextSearch\Service\RunningService;
use Symfony\Component\Console\Output\OutputInterface;


class Runner {


	/** @var RunningService */
	private $runningService;

	/** @var string */
	private $source;

	/** @var int */
	private $tickId;

	/** @var ExtendedBase */
	private $commandBase = null;

	/** @var OutputInterface */
	private $outputInterface = null;

	/**
	 * Runner constructor.
	 *
	 * @param RunningService $runningService
	 * @param string $source
	 */
	public function __construct(RunningService $runningService, $source) {
		$this->runningService = $runningService;
		$this->source = $source;
	}


	public function start() {
		$this->tickId = $this->runningService->start($this->source);
	}


	public function update($action) {
		$this->commandBase->hasBeenInterrupted();
		try {
			$this->runningService->update($this->tickId, $action);
		} catch (TickIsNotAliveException $e) {
			$this->output('Force Quit');
			exit();
		}
	}


	public function exception($reason, $stop) {
		if (!$stop) {
			$this->output('Exception: ' . $reason);
		}
		$this->runningService->exception($this->tickId, $reason, $stop);
	}


	public function stop() {
		$this->runningService->stop($this->tickId);
	}


	/**
	 * @param ExtendedBase $base
	 * @param OutputInterface $output
	 */
	public function sourceIsCommandLine(ExtendedBase $base, OutputInterface $output) {
		$this->outputInterface = $output;
		$this->commandBase = $base;
	}


	/**
	 * @param string $line
	 */
	public function output($line) {
		if ($this->outputInterface === null) {
			return;
		}

		$this->outputInterface->writeln($line);
	}


}