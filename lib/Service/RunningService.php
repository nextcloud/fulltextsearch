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


namespace OCA\FullTextSearch\Service;


use OCA\FullTextSearch\Db\TickRequest;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Exceptions\TickIsNotAliveException;
use OCA\FullTextSearch\Model\Tick;
use OCA\FullTextSearch\Model\Runner;


/**
 * Class RunningService
 *
 * @package OCA\FullTextSearch\Service
 */
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
	 * @param string $source
	 *
	 * @return int
	 * @throws RunnerAlreadyUpException
	 * @throws \Exception
	 */
	public function start(string $source): int {

		if ($this->isAlreadyRunning()) {
			throw new RunnerAlreadyUpException('Index is already running');
		}

		$tick = new Tick($source);
		$tick->setStatus('run')
			 ->setTick()
			 ->setFirstTick()
			 ->setInfoInt('runStart ', time());

		return $this->tickRequest->create($tick);
	}


	/**
	 * @param int $runId
	 * @param string $action
	 *
	 * @throws TickDoesNotExistException
	 * @throws TickIsNotAliveException
	 */
	public function update(int $runId, string $action = '') {
		$tick = $this->tickRequest->getTickById($runId);

		$this->isStillAlive($tick, true);
		$tick->setTick();

		if ($action !== '' && $action !== $tick->getAction()) {
			$this->assignActionToTick($tick, $action);
		}

		$this->tickRequest->update($tick);
	}


	/**
	 * @deprecated - verifier l'interet !
	 *
	 * @param int $runId
	 * @param string $reason
	 * @param bool $stop
	 */
	public function exception(int $runId, string $reason, bool $stop = false) {
		if ($stop) {
			try {
				$this->stop($runId, $reason);
			} catch (TickDoesNotExistException $e) {
				/** exception will be managed somewhere else */
				// TODO: Check if above statement is correct.
			}
		}
	}


	/**
	 * @param int $runId
	 * @param string $reason
	 *
	 * @throws TickDoesNotExistException
	 */
	public function stop(int $runId, string $reason = '') {
		$tick = $this->tickRequest->getTickById($runId);
		$tick->setStatus('stop')
			 ->setTick()
			 ->setInfoInt('runStop', time())
			 ->setInfoInt('totalDocuments', 42);

		if ($reason !== '') {
			$tick->setStatus('exception');
			$tick->setInfo('exception', $reason);
		}

		$this->tickRequest->update($tick);
	}


	/**
	 * @param int $runId
	 *
	 * @return bool
	 * @throws TickIsNotAliveException
	 */
	public function isAlive(int $runId): bool {
		$tick = null;
		try {
			$tick = $this->tickRequest->getTickById($runId);
		} catch (TickDoesNotExistException $e) {
			return false;
		}

		return $this->isStillAlive($tick);
	}


	/**
	 * @param Tick $tick
	 * @param bool $exception
	 *
	 * @return bool
	 * @throws TickIsNotAliveException
	 */
	public function isStillAlive(Tick $tick, bool $exception = false): bool {
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
	public function isAlreadyRunning(): bool {
		$ticks = $this->tickRequest->getTicksByStatus('run');

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
		$ticks = $this->tickRequest->getTicksByStatus('run');

		foreach ($ticks as $tick) {
			$tick->setStatus('forceStop');
			$this->tickRequest->update($tick);
		}
	}


	/**
	 * @param Tick $tick
	 * @param string $action
	 */
	private function assignActionToTick(Tick &$tick, string $action) {
		$now = microtime(true);
		$preAction = $tick->getAction();

		if ($preAction !== '') {
			$preActionTotal = $tick->getInfoFloat($preAction . 'Total', 0);
			$preActionStart = $tick->getInfoFloat($preAction . 'Init', 0);

			if ($preActionStart > 0) {

				$preActionTotal += ($now - $preActionStart);
				$tick->setInfoFloat($preAction . 'Total', $preActionTotal);
				$tick->unsetInfo($preAction . 'Init');
			}
		}
		$tick->setAction($action)
			 ->setInfoFloat($action . 'Init', $now);
	}


}
