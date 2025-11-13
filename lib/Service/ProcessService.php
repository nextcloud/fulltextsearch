<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use OCA\FullTextSearch\Model\DocumentSync;
use OCP\IDBConnection;

class ProcessService {
	private int $forkCount = 0;
	private const FORK_LIMIT = 8; // maximum number of child process
	private const FORK_SLEEP = 500000; // wait for msec when too many fork have been created
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	public function forkIndexDocument(DocumentSync $sync, callable $indexDocument): void {
		if (!extension_loaded('posix')) {
			$indexDocument($sync);
			return;
		}

		$pid = pcntl_fork();

		// work around as the parent database connection is inherited by the child.
		// when child process is over, parent process database connection will drop.
		// The drop can happen anytime, even in the middle of a running request.
		// work around is to close the connection as soon as possible after forking.
		$this->connection->close();

		if ($pid === -1) {
			// TODO: manage issue while forking
		} else if ($pid === 0) {
			// forked process
			$indexDocument($sync);
			exit();
		} else {
			// main process, counting forks
			$this->forkCount++;
			while (true) {
				if (pcntl_waitpid(0, $status, WNOHANG) !== 0) {
					$this->forkCount--;
				}
				if ($this->forkCount < self::FORK_LIMIT) {
					return;
				}
				usleep(self::FORK_SLEEP);
			}
		}
	}

	public function waitForChild(): void {
		if (!extension_loaded('posix')) {
			return;
		}

		while (pcntl_waitpid(0, $status) !== -1) {
		}
	}
}
