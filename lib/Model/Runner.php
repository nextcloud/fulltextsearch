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

	/** @var array */
	private $info = [];

	/** @var int */
	private $oldTick = 0;

	/** @var string */
	private $oldAction = '';

	/** @var int */
	private $ramTick = 0;

	/** @var array */
	private $methodOnKeyPress = [];

	/** @var array */
	private $methodOnNewAction = [];

	/** @var array */
	private $methodOnInfoUpdate = [];

	/** @var array */
	private $methodOnIndexError = [];

	/** @var bool */
	private $paused = false;

	/** @var bool */
	private $pauseRunning = false;

	/** @var array */
	private $keys = ['nextStep' => 'n'];


	/**
	 * Runner constructor.
	 *
	 * @param RunningService $runningService
	 * @param string $source
	 * @param array $keys
	 */
	public function __construct(RunningService $runningService, $source, $keys = []) {
		$this->runningService = $runningService;
		$this->source = $source;

		if (sizeof($keys) > 0) {
			$this->keys = $keys;
		}
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
	 * @param string $action
	 * @param bool $force
	 *
	 * @return string
	 * @throws InterruptException
	 * @throws TickDoesNotExistException
	 */
	public function updateAction($action = '', $force = false) {
		$n = '';
		if (sizeof($this->methodOnKeyPress) > 0) {
			$n = fread(STDIN, 9999);
			if ($n !== '') {
				$n = substr($n, 0, 1);
				$this->keyPressed($n);
			}
		}

		if ($action === '') {
			return $n;
		}

		$tick = time();
		try {
			$this->hasBeenInterrupted();
		} catch (InterruptException $e) {
			$this->stop();
			throw $e;
		}

		if ($this->oldAction !== $action || $force) {
			while (true) {
				if (!$this->isPaused()) {
					$this->pauseRunning(false);
					break;
				}

				$this->pauseRunning(true);
				$pressed = strtolower($this->updateAction(''));
				if ($pressed === $this->keys['nextStep']) {
					$this->pauseRunning(false);
					break;
				}
				usleep(300000);
			}

			$this->newAction($action);
		}

		if ($this->oldAction === $action && ($this->oldTick + self::TICK_MINIMUM > $tick)) {
			return '';
		}

		$this->setInfo('action', $action);
		try {
			$this->runningService->update($this->tickId, $action);
		} catch (TickIsNotAliveException $e) {
			$this->output('Force Quit');
			exit();
		}

		$this->updateTick($tick);
		$this->oldAction = $action;
		$this->oldTick = $tick;

		return '';
	}


	/**
	 * @param string $info
	 * @param string $value
	 * @param string $colored
	 */
	public function setInfo($info, $value, $colored = '') {
		$this->info[$info] = $value;
		$this->setInfoColored($info, $value, $colored);
		$this->infoUpdated();
	}

	/**
	 * @param array $data
	 */
	public function setInfoArray($data) {
		$keys = array_keys($data);
		//$this->info['info'] = '';
		foreach ($keys as $k) {
			$this->info[$k] = $data[$k];
		}

		$this->infoUpdated();
	}

	private function setInfoColored($info, $value, $colored) {
		if ($colored === '') {
			return;
		}

		$color = '';
		switch ($colored) {
			case 'success':
				$color = 'info';
				break;

			case 'fail' :
				$color = 'error';
				break;

			case 'warning':
				$color = 'comment';
				break;
		}

		if ($color !== '') {
			$this->info[$info . 'Colored'] = '<' . $color . '>' . $value . '</' . $color . '>';
		}


	}

	/**
	 * @return array
	 */
	public function getInfo() {
		return $this->info;
	}


	/**
	 * @param array $method
	 */
	public function onKeyPress($method) {
		$this->methodOnKeyPress[] = $method;
	}

	/**
	 * @param $key
	 */
	public function keyPressed($key) {
		foreach ($this->methodOnKeyPress as $method) {
			call_user_func($method, $key);
		}
	}


	/**
	 * @param array $method
	 */
	public function onNewAction($method) {
		$this->methodOnNewAction[] = $method;
	}

	/**
	 * @param string $action
	 */
	public function newAction($action) {
		foreach ($this->methodOnNewAction as $method) {
			call_user_func($method, $action);
		}
	}


	/**
	 * @param array $method
	 */
	public function onInfoUpdate($method) {
		$this->methodOnInfoUpdate[] = $method;
	}

	/**
	 * @param $key
	 */
	public function infoUpdated() {
		foreach ($this->methodOnInfoUpdate as $method) {
			call_user_func($method, $this->info);
		}
	}


	/**
	 * @param array $method
	 */
	public function onNewIndexError($method) {
		$this->methodOnIndexError[] = $method;
	}

	/**
	 * @param Index $index
	 * @param string $message
	 * @param string $class
	 * @param int $sev
	 */
	public function newIndexError($index, $message, $class = '', $sev = 3) {
		$error = [
			'index'     => $index,
			'message'   => $message,
			'exception' => $class,
			'severity'  => $sev
		];

		foreach ($this->methodOnIndexError as $method) {
			call_user_func($method, $error);
		}
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
	private function updateTick($tick) {
		if (($this->ramTick + self::INFO_UPDATE) > $tick) {
			return;
		}

		$this->setInfo('_memory', round((memory_get_usage() / 1024 / 1024)) . ' MB');
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


	/**
	 * @param bool $pause
	 */
	public function pause($pause) {
		$this->paused = $pause;
		$this->infoUpdated();
	}

	/**
	 * @return bool
	 */
	public function isPaused() {
		return $this->paused;
	}


	/**
	 * @param bool $running
	 */
	public function pauseRunning($running) {
		$this->pauseRunning = $running;
		$this->infoUpdated();
	}


	public function isPauseRunning() {
		return $this->pauseRunning;
	}


	/**
	 * @return bool
	 */
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