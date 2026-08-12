<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OCA\FullTextSearch\Service\RunningService;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:stop',
	description: 'Stop all indexing',
)]
class Stop {
	public function __construct(
		private RunningService $runningService,
	) {
	}

	public function __invoke(IOutput $output): ExitCode {
		$output->writeln('stopping all running indexes');

		$this->runningService->forceStop();

		return ExitCode::Success;
	}


}
