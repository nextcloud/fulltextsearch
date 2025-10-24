<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\ConfigLexicon;
use OCA\FullTextSearch\Exceptions\LockException;
use OCP\IAppConfig;
use OCP\Security\ISecureRandom;

class LockService {
	private const LOCK_TIMEOUT = 300;     // the final value will be LOCK_TIMEOUT+LOCK_PING_DELAY
	private const LOCK_PING_DELAY = 10;

	private string $lockId;
	private int $nextPing = -1;

	public function __construct(
		ISecureRandom $random,
		private IAppConfig $appConfig,
	) {
		$this->lockId = $random->generate(7);
	}

	/**
	 * Lock the index to this process for few minutes.
	 * Needs to be refreshed to keep lock alive.
	 *
	 * _**Warning:** reload lazy app config._
	 *
	 * @throws LockException if the index is already running
	 */
	public function lock(): void {
		$this->appConfig->clearCache(true);
		$currentLockPing = $this->appConfig->getValueInt(Application::APP_ID, ConfigLexicon::LOCK_PING);
		$currentLockId = $this->appConfig->getValueString(Application::APP_ID, ConfigLexicon::LOCK_ID);

		// previous lock timeout
		if ($currentLockPing < time()) {
			$currentLockId = '';
		}

		// new lock; enforce ping on new lock
		if ($currentLockId === '') {
			$this->appConfig->setValueString(Application::APP_ID, ConfigLexicon::LOCK_ID, $this->lockId);
			$currentLockId = $this->lockId;
			$this->nextPing = 0;
		}

		// confirm the lock belongs to the current process
		if ($currentLockId !== $this->lockId) {
			throw new LockException('Index is already running');
		}

		$this->update();
	}

	public function update(): void {
		if ($this->nextPing === -1) {
			throw new LockException('lock service not initiated on this process');
		}

		$time = time();

		// update ping
		if ($this->nextPing < $time) {
			$this->nextPing = $time + self::LOCK_PING_DELAY;
			$this->appConfig->setValueInt(Application::APP_ID, ConfigLexicon::LOCK_PING, $this->nextPing + self::LOCK_TIMEOUT);
		}
	}

	public function unlock(): void {
		$this->appConfig->deleteKey(Application::APP_ID, ConfigLexicon::LOCK_PING);
		$this->nextPing = -1;
	}
}
