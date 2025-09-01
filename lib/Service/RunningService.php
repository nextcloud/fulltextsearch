<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use Exception;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Db\TickRequest;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;
use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Exceptions\TickIsNotAliveException;
use OCA\FullTextSearch\Model\Tick;
use OCP\AppFramework\Services\IAppConfig;

class RunningService {
	public function __construct(
		private TickRequest $tickRequest,
		private readonly IAppConfig $appConfig,
	) {
	}

	/**
	 * @param string $source
	 *
	 * @return int
	 * @throws RunnerAlreadyUpException
	 * @throws Exception
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
		$ttl = $this->appConfig->getAppValueInt(ConfigLexicon::TICK_TTL);
		$ticks = $this->tickRequest->getTicksByStatus('run');

		$isAlreadyRunning = false;
		foreach ($ticks as $tick) {
			if ($tick->getTick() < (time() - $ttl)) {
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
	private function assignActionToTick(Tick $tick, string $action) {
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
