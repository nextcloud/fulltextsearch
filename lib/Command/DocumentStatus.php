<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Service\IndexService;
use OCP\FullTextSearch\Model\IIndex;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentStatus extends Base {
	private array $statusAvailable = [
		'IGNORE' => 'document will never be indexed',
		'INDEX'  => 'document will be indexed',
		'DONE'   => 'document is well indexed',
		'REMOVE' => 'document will be removed',
		'FAILED' => 'index had fail'
	];

	public function __construct(
		private IndexService $indexService
	) {
		parent::__construct();
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:document:status')
			 ->setDescription('change the status on one specific document')
			 ->addArgument('provider', InputArgument::REQUIRED, 'Id of the provider')
			 ->addArgument('document', InputArgument::REQUIRED, 'If of the document')
			 ->addOption('value', '', InputOption::VALUE_REQUIRED, 'new status', '')
			 ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'specify the owner of the document', '')
			 ->addOption('json', 'j', InputOption::VALUE_NONE, 'return status in JSON');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$providerId = $input->getArgument('provider');
		$documentId = $input->getArgument('document');
		$value = $input->getOption('value');
		$userId = $input->getOption('user');
		$json = $input->getOption('json');

		try {
			$index = $this->indexService->getIndex($providerId, $documentId);
			if ($value !== '') {
				$status = $this->statusConvertFromString($value);
				$index->setStatus($status, true);
				$this->indexService->updateIndex($index);
			}
		} catch (IndexDoesNotExistException $e) {
			if ($userId === '') {
				throw new Exception(
					"Index is not known.\nIf you want to generate the entry, please specify the owner of the document using --user <userId>"
				);
			}

			$status = $this->statusConvertFromString($value);
			$index = $this->indexService->createIndex($providerId, $documentId, $userId, $status);
		}


		if ($json) {
			echo json_encode($index, JSON_PRETTY_PRINT) . "\n";

			return 0;
		}

		$status = $this->statusConvertToString($index->getStatus());
		$desc = $this->statusAvailable[$status];
		$output->writeln('current status: <info>' . $status . '</info> (' . $desc . ')');

		return 0;
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
			case IIndex:: INDEX_PARTS:
			case IIndex:: INDEX_FULL:
				return 'INDEX';

			case IIndex:: INDEX_REMOVE:
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
				return IIndex:: INDEX_FULL;

			case 'REMOVE':
				return IIndex:: INDEX_REMOVE;

			case 'FAILED':
				return IIndex::INDEX_FAILED;
		}

		throw new Exception("Specify a valid status: " . implode(', ', array_keys($this->statusAvailable)));
	}

}

