<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Service\IndexService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\OutputFormat;
use OCP\FullTextSearch\Model\IIndex;

#[AsCommand(
	name: 'fulltextsearch:document:status',
	description: 'change the status on one specific document',
	supportsOutputFormat: true,
)]
class DocumentStatus {
	private array $statusAvailable = [
		'IGNORE' => 'document will never be indexed',
		'INDEX' => 'document will be indexed',
		'DONE' => 'document is well indexed',
		'REMOVE' => 'document will be removed',
		'FAILED' => 'index had fail'
	];

	public function __construct(
		private IndexService $indexService,
	) {
	}


	/**
	 * @throws Exception
	 */
	public function __invoke(
		IOutput $output,
		OutputFormat $outputFormat,
		#[Argument(description: 'Id of the provider')]
		string $provider,
		#[Argument(description: 'If of the document')]
		string $document,
		#[Option(description: 'new status')]
		string $value = '',
		#[Option(description: 'specify the owner of the document', shortcut: 'u')]
		string $user = '',
	): ExitCode {
		try {
			$index = $this->indexService->getIndex($provider, $document);
			if ($value !== '') {
				$status = $this->statusConvertFromString($value);
				$index->setStatus($status, true);
				$this->indexService->updateIndex($index);
			}
		} catch (IndexDoesNotExistException $e) {
			if ($user === '') {
				throw new Exception(
					"Index is not known.\nIf you want to generate the entry, please specify the owner of the document using --user <userId>"
				);
			}

			$status = $this->statusConvertFromString($value);
			$index = $this->indexService->createIndex($provider, $document, $user, $status);
		}


		if ($outputFormat !== OutputFormat::Plain) {
			$output->writeArrayInOutputFormat(json_decode(json_encode($index), true));

			return ExitCode::Success;
		}

		$status = $this->statusConvertToString($index->getStatus());
		$desc = $this->statusAvailable[$status];
		$output->writeln('current status: <info>' . $status . '</info> (' . $desc . ')');

		return ExitCode::Success;
	}


	/**
	 * @param int $status
	 *
	 * @return string
	 */
	private function statusConvertToString(int $status): string {
		switch ($status) {
			case IIndex::INDEX_OK:
			case IIndex::INDEX_DONE:
				return 'DONE';

			case IIndex::INDEX_IGNORE:
				return 'IGNORE';

			case IIndex::INDEX_META:
			case IIndex::INDEX_CONTENT:
			case IIndex::INDEX_PARTS:
			case IIndex::INDEX_FULL:
				return 'INDEX';

			case IIndex::INDEX_REMOVE:
				return 'REMOVE';

			case IIndex::INDEX_FAILED:
				return 'FAILED';
		}

		return 'unknown';
	}


	/**
	 * @param string $status
	 *
	 * @return int
	 * @throws Exception
	 */
	private function statusConvertFromString(string $status): int {
		switch ($status) {
			case 'DONE':
				return IIndex::INDEX_OK;

			case 'IGNORE':
				return IIndex::INDEX_IGNORE;

			case 'INDEX':
				return IIndex::INDEX_FULL;

			case 'REMOVE':
				return IIndex::INDEX_REMOVE;

			case 'FAILED':
				return IIndex::INDEX_FAILED;
		}

		throw new Exception('Specify a valid status: ' . implode(', ', array_keys($this->statusAvailable)));
	}

}
