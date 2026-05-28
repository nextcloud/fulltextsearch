<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use OCA\FullTextSearch\Exceptions\LockException;
use OCA\FullTextSearch\Exceptions\RunnerAlreadyUpException;

/**
 * @deprecated
 * @see LockService
 */
class RunningService {
	public function __construct(
		private readonly LockService $lockService,
	) {
	}

	/**
	 * @deprecated
	 */
	public function start(string $source): int {
		try {
			$this->lockService->lock();
		} catch (LockException $e) {
			throw new RunnerAlreadyUpException($e->getMessage());
		}
		return 1;
	}

	/**
	 * @deprecated
	 */
	public function update(int $runId, string $action = '') {
		try {
			$this->lockService->update();
		} catch (LockException $e) {
			throw new RunnerAlreadyUpException($e->getMessage());
		}
	}

	/**
	 * @deprecated
	 */
	public function stop(int $runId, string $reason = '') {
		$this->lockService->unlock();
	}
}
