<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use OCA\FullTextSearch\Service\ConfigService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:configure',
	description: 'Configure the installation',
)]
class Configure {
	public function __construct(
		private ConfigService $configService,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'set config')]
		string $json = '',
	): ExitCode {
		if ($json !== '') {
			$this->configService->setConfig(json_decode($json, true) ?? []);
		}

		$output->writeln(json_encode($this->configService->getConfig(), JSON_PRETTY_PRINT));

		return ExitCode::Success;
	}
}
