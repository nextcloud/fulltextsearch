<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;

use NCU\FullTextSearch\ILoggerService;
use OCA\FullTextSearch\Enum\SessionType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoggerService implements ILoggerService {
	private ?OutputInterface $output = null;
	private bool $verbose = false;
	private SessionType $currentSessionType = SessionType::CLOSED;

	public function __construct(
		private ?LoggerInterface $logger,
		private readonly LoggerInterface $loggerError,
	) {
	}

	/**
	 * override debug in nextcloud log with an output interface
	 */
	public function setOutputInterface(OutputInterface $output, bool $verbose = false): void {
		$this->verbose = $verbose;
		$this->output = $output;
		$this->logger = null;
	}

	public function info(string $entry): void {
		if ($this->verbose) {
			$this->output?->writeln($this->prepOutput() . ' ' . $this->prepEntry($entry, 'comment'));
		}
		$this->logger?->debug('[' . $this->currentSessionType->value . '] ' . 'info: ' . $entry);
	}

	public function action(string $entry): void {
		$this->output?->writeln($this->prepOutput() . ' ' . $this->prepEntry($entry));
		$this->logger?->debug('[' . $this->currentSessionType->value . '] ' . 'action: ' . $entry);
	}

	public function warning(string $entry, array $data = []): void {
		$this->output?->writeln($this->prepOutput() . ' ' . $this->prepEntry($entry, 'error'));
		$this->logger?->warning('[' . $this->currentSessionType->value . '] ' . $entry, $data);
	}

	public function error(string $entry, array $data = []): void {
		$this->output?->writeln($this->prepOutput() . ' ' . $this->prepEntry($entry, 'error'));
		$this->loggerError->warning('[' . $this->currentSessionType->value . '] ' . $entry, $data);
	}

	public function session(SessionType $sessionType = SessionType::CLOSED): void {
		$this->currentSessionType = $sessionType;
	}

	private function prepOutput(): string {
		return '<fg=gray>' . date('[H:i:s]') . '</> ';
	}

	private function prepEntry(string $entry, string $tag = ''): string {
		$entry = ($tag !== '') ? '<' . $tag . '>' . $entry . '</>' : $entry;
		$prefix = '<fg=gray>' . str_pad(($this->currentSessionType === SessionType::CLOSED) ? '' : '(' . $this->currentSessionType->value . ')', 14, ' ') . '</>';
		return $prefix . $entry;
	}
}
