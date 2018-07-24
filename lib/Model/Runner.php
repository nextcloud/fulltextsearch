<?php
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


namespace OCA\FullTextSearch\Model;

use Exception;
use OCA\FullTextSearch\Exceptions\InterruptException;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Exceptions\TickIsNotAliveException;
use OCA\FullTextSearch\Service\RunningService;
use Symfony\Component\Console\Output\OutputInterface;


class Runner {


	const TICK_TTL = 300;
	const TICK_MINIMUM = 2;
	const INFO_UPDATE = 10;

	/** @var RunningService */
	private $runningService;

	/** @var string */
	private $source;

	/** @var bool */
	private $strict = false;

	/** @var int */
	private $tickId;

	/** @var ExtendedBase */
	private $commandBase = null;

	/** @var OutputInterface */
	private $outputInterface = null;

	/** @var int */
	private $oldTick = 0;

	/** @var string */
	private $oldAction = '';

	/** @var int */
	private $ramTick = 0;

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


	/**
	 * @param bool $strict
	 *
	 * @throws RunnerAlreadyUpException
	 */
	public function start($strict = false) {
		$this->strict = $strict;
		$this->tickId = $this->runningService->start($this->source);
	}


	/**
	 * @param $action
	 *
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	public function update($action) {

		$tick = time();
		try {
			$this->hasBeenInterrupted();
		} catch (InterruptException $e) {
			$this->stop();
			throw $e;
		}

		if ($this->oldAction === $action && ($this->oldTick + self::TICK_MINIMUM > $tick)) {
			return;
		}

		try {
			$this->runningService->update($this->tickId, $action);
		} catch (TickIsNotAliveException $e) {
			$this->output('Force Quit');
			exit();
		}

		$this->updateInfo($tick);
		$this->oldAction = $action;
		$this->oldTick = $tick;
	}


	/**
	 * @throws InterruptException
	 */
	private function hasBeenInterrupted() {
		if ($this->commandBase === null) {
			return;
		}
		$this->commandBase->hasBeenInterrupted();
	}


	/**
	 * @param $tick
	 */
	private function updateInfo($tick) {
		if (($this->ramTick + self::INFO_UPDATE) > $tick) {
			return;
		}

		$this->output('- RAM: ' . (memory_get_usage() / 1024 / 1024));
		$this->ramTick = $tick;
	}


	public function exception($reason, $stop) {
		if (!$stop) {
			$this->output('Exception: ' . $reason);
			// TODO: feed an array of exceptions for log;
		}
		$this->runningService->exception($this->tickId, $reason, $stop);
	}


	/**
	 * @throws TickDoesNotExistException
	 */
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


	public function isStrict() {
		return $this->strict;
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