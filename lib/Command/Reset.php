<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\RunningService;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Console\ISignalHandler;

/**
 * Class Reset
 *
 * @package OCA\FullTextSearch\Command
 */
#[AsCommand(
	name: 'fulltextsearch:reset',
	description: 'Reset index',
)]
class Reset {

	private Runner $runner;

	public function __construct(
		RunningService $runningService,
		private IndexService $indexService,
	) {
		$this->runner = new Runner($runningService, 'commandReset');
	}


	/**
	 * @throws Exception
	 */
	public function __invoke(
		IOutput $output,
		IInput $input,
		ISignalHandler $signalHandler,
		#[Option(description: 'provider id')]
		string $provider = '',
		#[Option(description: 'name of the collection')]
		string $collection = '',
	): ExitCode {
		$output->writeln('<error>WARNING! You are about to reset your indexed documents:</error>');
		$output->writeln('- provider: <info>' . (($provider === '') ? 'ALL' : $provider) . '</info>');
		$output->writeln('- collection: <info>' . (($collection === '') ? 'ALL' : $collection) . '</info>');
		$output->writeln('');

		if (!$input->confirm('Do you really want to reset your indexed documents?', false)) {
			$output->writeln('');
			$output->writeln('aborted.');

			return ExitCode::Success;
		}

		$output->writeln('');
		$output->writeln('<error>WARNING! This operation is not reversible.</error>');
		$action = 'reset ' . (($provider === '') ? 'ALL' : $provider)
			. ' ' . (($collection === '') ? 'ALL' : $collection);

		$confirmation = (string)$input->ask('Please confirm this destructive operation by typing \'' . $action . '\'', '');
		if (strtolower($confirmation) !== strtolower($action)) {
			$output->writeln('');
			$output->writeln('aborted.');

			return ExitCode::Success;
		}

		try {
			$this->runner->sourceIsCommandLine($signalHandler, $output);
			$this->runner->start();
		} catch (Exception $e) {
			$this->runner->exception($e->getMessage(), true);
			throw $e;
		}

		$this->indexService->setRunner($this->runner);
		try {
			$this->indexService->resetIndex($provider, $collection);
			$output->writeln('');
			$output->writeln('done.');

		} catch (Exception $e) {
			throw $e;
		} finally {
			$this->runner->stop();
		}

		return ExitCode::Success;
	}
}
