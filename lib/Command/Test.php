<?php

/**
 * Nextcloud - nextant
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@pontapreta.net>
 * @copyright Maxence Lange 2017
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
namespace OCA\Nextant\Command;

use OC\Core\Command\Base;
use \OCA\Nextant\Service\SolrAdminService;
use \OCA\Nextant\Service\SolrToolsService;
use \OCA\Nextant\Service\SolrService;
use \OCA\Nextant\Service\TestService;
use \OCA\Nextant\Service\IndexService;
use \OCA\Nextant\Service\ConfigService;
use \OCA\Nextant\Controller\SettingsController;
use \OCA\Nextant\Items\ItemDocument;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Test extends Base {

	private $configService;

	private $solrService;

	private $solrTools;

	private $solrAdmin;

	private $settingsController;

	public function __construct(
		ConfigService $configService, SolrService $solrService, SolrToolsService $solrTools,
		SolrAdminService $solrAdmin, IndexService $indexService,
		SettingsController $settingsController
	) {
		parent::__construct();
		$this->configService = $configService;
		$this->solrService = $solrService;
		$this->solrTools = $solrTools;
		$this->solrAdmin = $solrAdmin;
		$this->indexService = $indexService;
		$this->settingsController = $settingsController;
	}

	protected function configure() {
		parent::configure();
		$this->setName('nextant:test')
			 ->setDescription('test your Nextant configuration')
			 ->addArgument('address', InputArgument::REQUIRED, 'address of the solr to test')
			 ->addArgument('core', InputArgument::REQUIRED, 'core to test');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		if (!$address = $input->getArgument('address')) {
			$output->writeln('You need to specify the address to test');

			return;
		}

		if (!$core = $input->getArgument('core')) {
			$output->writeln('You need to specify the core');

			return;
		}

		$tmpConfig = array(
			'solr_url'     => $address,
			'solr_core'    => $core,
			'solr_timeout' => 30
		);

		if (!$this->solrService->setClient($tmpConfig)) {
			$output->writeln('Address is invalid');

			return;
		}

		// test ping
		if (!$this->test_ping($output)) {
			return;
		}

		// test schema
		if (!$this->test_schema($output)) {
			return;
		}

		// test extract
		if (!$this->test_extract($output)) {
			return;
		}

		// test update
		if (!$this->test_update($output)) {
			return;
		}

		// test search
		if (!$this->test_search($output)) {
			return;
		}

		// test delete
		if (!$this->test_delete($output)) {
			return;
		}

		$output->writeln('');
		$output->writeln('All test results were fine');
	}

	private function test_ping($output) {
		$output->write(' - Pinging Solr: ');
		if (!$this->solrAdmin->ping($ierror)) {
			$output->writeln(
				'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
			);

			return false;
		}

		$output->writeln('<info>ok</info>');

		return true;
	}

	private function test_schema($output) {
		$output->write(' - Checking Solr schema: ');
		$ierror = null;
		if (!$this->solrAdmin->checkSchema(true, $ierror)) {
			$output->writeln(
				'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
			);

			return false;
		}

		$output->writeln('<info>ok</info>');

		return true;
	}

	private function test_extract($output) {
		$ierror = null;
		$output->write(' - Extracting test document: ');
		$doc = TestService::generateTestDocument(
			1, '_nextant_test', __DIR__ . '/../../LICENSE', '/LICENSE'
		);
		$data = array(
			$doc
		);
		$solrDocs = null;
		$this->indexService->extract(
			ItemDocument::TYPE_TEST, '_nextant_test', $data, $solrDocs, true, $ierror
		);

		if (!$doc->isProcessed()) {
			$output->writeln(
				'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
			);

			return false;
		}

		$output->writeln('<info>ok</info>');

		return true;
	}

	private function test_update($output) {
		$ierror = null;
		$output->write(' - Updating test document: ');
		$doc = TestService::generateTestDocument(
			1, '_nextant_test', __DIR__ . '/../../LICENSE', '/LICENSE2'
		);
		$asource =
			$this->indexService->getDocuments(ItemDocument::TYPE_TEST, '_nextant_test', 1, $ierror);

		if ($asource === false || sizeof($asource) != 1 || (!key_exists('test_1', $asource))) {
			$output->writeln(
				'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
			);

			return false;
		}

		$source = $asource['test_1'];

		$doc->setPath('/LICENSE2');
		$doc->setShare(
			array(
				'nextant_test_share'
			)
		);
		$doc->setShareGroup(
			array(
				'nextant_test_share_group'
			)
		);
		$doc->deleted(false);

		$data = array(
			$doc
		);
		$this->indexService->updateDocuments(
			ItemDocument::TYPE_TEST, '_nextant_test', $data, $asource, $ierror
		);

		if (!$this->solrTools->commit(false, $ierror)) {
			$output->writeln(
				'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
			);

			return false;
		}

		if (!$source->isUpdated()) {
			$output->writeln(
				'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
			);

			return false;
		}

		$output->writeln('<info>ok</info>');

		return true;
	}

	private function test_search($output) {
		$output->write(' - Searching test document: ');
		$ierror = null;
		$keyword = 'LICENSE';
		$this->solrService->setOwner('_nextant_test');
		if ($result = $this->solrService->search($keyword, array(), $ierror)) {
			if (sizeof($result) > 0) {

				foreach ($result as $doc) {
					if ($doc->getType() === ItemDocument::TYPE_TEST && $doc->getId() === 1) {
						$output->writeln('<info>ok</info>');

						return true;
					}
				}

				// CHECK ID DOCUMENT
				$output->writeln('<error>fail</error>');

				return false;
			}

			$output->writeln('<error>fail</error>');

			return false;
		}

		$output->writeln(
			'<error>fail</error> - ' . (($ierror === null) ? "0" : $ierror->getCode())
		);

		return false;
	}

	private function test_delete($output) {
		$ierror = null;
		$output->write(' - Deleting test document: ');

		$doc = new ItemDocument(ItemDocument::TYPE_TEST, 1);
		$data = array(
			$doc
		);
		$this->indexService->removeDocuments($data, $ierror);
		if ($doc->isRemoved()) {
			$output->writeln('<info>ok</info>');

			return true;
		}

		$output->writeln('<error>fail</error> - ' . ($ierror === null) ? "0" : $ierror->getCode());

		return false;
	}
}



