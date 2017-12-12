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

namespace OCA\FullNextSearch\Service;

use OCA\FullNextSearch\Db\TickRequest;
use OCA\FullNextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullNextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullNextSearch\Exceptions\TickIsNotAliveException;
use OCA\FullNextSearch\Model\ExtendedTick;
use OCA\FullNextSearch\Model\Runner;

class RunningService {

	/** @var TickRequest */
	private $tickRequest;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * RunningService constructor.
	 *
	 * @param TickRequest $tickRequest
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		TickRequest $tickRequest, ConfigService $configService, MiscService $miscService
	) {
		$this->tickRequest = $tickRequest;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param $source
	 *
	 * @return int
	 * @throws RunnerAlreadyUpException
	 */
	public function start($source) {

		if ($this->isAlreadyRunning()) {
			throw new RunnerAlreadyUpException('Index is already running');
		}

		$tick = new ExtendedTick($source);
		$tick->setStatus('run')
			 ->setTick()
			 ->setInfo('runStart ', time());

		return $this->tickRequest->create($tick);
	}


	/**
	 * @param int $runId
	 * @param string $action
	 *
	 * @throws TickIsNotAliveException
	 */
	public function update($runId, $action = '') {
		$tick = $this->tickRequest->getTickById($runId);

		$this->isStillAlive($tick, true);
		$tick->setTick();

		if ($action !== '' && $action !== $tick->getAction()) {
			$this->assignActionToTick($tick, $action);
		}

		$this->tickRequest->update($tick);
	}


	/**
	 * @param int $runId
	 * @param string $reason
	 * @param bool $stop
	 */
	public function exception($runId, $reason, $stop = false) {
		if ($stop) {
			try {
				$this->stop($runId, $reason);
			} catch (TickDoesNotExistException $e) {
				/** exception will be managed somewhere else */
			}
		}
	}


	/**
	 * @param int $runId
	 * @param string $reason
	 */
	public function stop($runId, $reason = '') {
		$tick = $this->tickRequest->getTickById($runId);
		$tick->setStatus('stop')
			 ->setTick()
			 ->setInfo('stopTime', time())
			 ->setInfo('totalDocuments', 42);

		if ($reason !== '') {
			$tick->setStatus('exception');
			$tick->setInfo('exception', $reason);
		}

		$this->tickRequest->update($tick);
	}


	/**
	 * @param $runId
	 *
	 * @return bool
	 */
	public function isAlive($runId) {
		$tick = null;
		try {
			$tick = $this->tickRequest->getTickById($runId);
		} catch (TickDoesNotExistException $e) {
			return false;
		}

		return $this->isStillAlive($tick);
	}


	/**
	 * @param ExtendedTick $tick
	 * @param bool $exception
	 *
	 * @return bool
	 * @throws TickIsNotAliveException
	 */
	public function isStillAlive(ExtendedTick $tick, $exception = false) {
		if ($tick->getStatus() !== 'run') {
			if ($exception) {
				throw new TickIsNotAliveException();
			} else {
				return false;
			}
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function isAlreadyRunning() {
		$ticks = $this->tickRequest->getTickByStatus('run');

		$isAlreadyRunning = false;
		foreach ($ticks as $tick) {
			if ($tick->getTick() < (time() - Runner::TICK_TTL)) {
				$tick->setStatus('timeout');
				$this->tickRequest->update($tick);
			} else {
				$isAlreadyRunning = true;
			}
		}

		return $isAlreadyRunning;
	}


	/**
	 *
	 */
	public function forceStop() {
		$ticks = $this->tickRequest->getTickByStatus('run');

		foreach ($ticks as $tick) {
			$tick->setStatus('forceStop');
			$this->tickRequest->update($tick);
		}
	}


	/**
	 * @param ExtendedTick $tick
	 * @param string $action
	 */
	private function assignActionToTick(ExtendedTick &$tick, $action) {
		$now = MiscService::getMicroTime(microtime(true));
		$preAction = $tick->getAction();

		if ($preAction !== '') {
			$preActionTotal = $tick->getInfo($preAction . 'Total', 0);
			$preActionStart = $tick->getInfo($preAction . 'Init', 0);

			if ($preActionStart > 0) {

				$preActionTotal += ($now - $preActionStart);
				$tick->setInfo($preAction . 'Total', $preActionTotal);
				$tick->unsetInfo($preAction . 'Init');
			}
		}
		$tick->setAction($action)
			 ->setInfo($action . 'Init', $now);
	}


}