<?php
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

namespace OCA\FullTextSearch\Migration;

use OCA\FullTextSearch\Db\IndexesRequest;
use OCA\FullTextSearch\Service\ProviderService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class ResetDatabase
 *
 * @package OCA\FullTextSearch\Migration
 */
class ResetIndexes implements IRepairStep {

	/** @var IndexesRequest */
	protected $indexRequest;

	/** @var ProviderService */
	protected $providerService;


	/**
	 * ResetDatabase constructor.
	 *
	 * @param IndexesRequest $indexRequest
	 * @param ProviderService $providerService
	 */
	public function __construct(IndexesRequest $indexRequest, ProviderService $providerService) {
		$this->indexRequest = $indexRequest;
		$this->providerService = $providerService;
	}


	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Reset index';
	}


	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {

		$oldVersion = explode(
			'.', \OC::$server->getConfig()
							 ->getAppValue('fulltextsearch', 'installed_version', '')
		);

		if ((int)$oldVersion[0] === 0
			&& (int)$oldVersion[1] < 6) {
			$this->resetIndexes($output);
		}

	}


	/**
	 * @param IOutput $output
	 */
	public function resetIndexes(IOutput $output) {
		$output->info('Reset database');

//		$this->indexRequest->reset();
//		$this->providerService->setProvidersAsNotIndexed();


		$output->finishProgress();
	}

}
