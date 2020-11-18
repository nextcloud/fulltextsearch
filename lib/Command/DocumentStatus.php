<?php declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\FullTextSearch\Command;


use Exception;
use OC\Core\Command\Base;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Service\IndexService;
use OCA\FullTextSearch\Service\MiscService;
use OCP\FullTextSearch\Model\IIndex;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class DocumentStatus
 *
 * @package OCA\FullTextSearch\Command
 */
class DocumentStatus extends Base {


	/** @var IndexService */
	private $indexService;

	/** @var MiscService */
	private $miscService;

	/** @var array */
	private $statusAvailable = [
		'IGNORE' => 'document will never be indexed',
		'INDEX'  => 'document will be indexed',
		'DONE'   => 'document is well indexed',
		'REMOVE' => 'document will be removed',
		'FAILED' => 'index had fail'
	];


	/**
	 * DocumentStatus constructor.
	 *
	 * @param IndexService $indexService
	 * @param MiscService $miscService
	 */
	public function __construct(IndexService $indexService, MiscService $miscService) {
		parent::__construct();

		$this->indexService = $indexService;
		$this->miscService = $miscService;
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

