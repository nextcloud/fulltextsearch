<?php
/**
 * FullNextSearch - Full Text Search your Nextcloud.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2017
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
 *
 */

namespace OCA\FullNextSearch\Command;

use Exception;
use OCA\FullNextSearch\INextSearchProvider;
use OCA\FullNextSearch\Model\ExtendedBase;
use OCA\FullNextSearch\Service\IndexService;
use OCA\FullNextSearch\Service\MiscService;
use OCA\FullNextSearch\Service\ProviderService;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Index extends ExtendedBase {

	/** @var IUserManager */
	private $userManager;

	/** @var IndexService */
	private $indexService;

	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IndexService $indexService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IUserManager $userManager, IndexService $indexService, ProviderService $providerService,
		MiscService $miscService
	) {
		parent::__construct();
		$this->userManager = $userManager;

		$this->indexService = $indexService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	protected function configure() {
		parent::configure();
		$this->setName('fullnextsearch:index')
			 ->setDescription('Index files');
	}


	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln('index');

		$this->setOutput($output);
		try {

			$providers = $this->providerService->getProviders();
			foreach ($providers as $provider) {
				$this->indexProvider($provider, $input, $output);
			}

		} catch (Exception $e) {
			throw $e;
		}
	}


	private function indexProvider(
		INextSearchProvider $provider, InputInterface $input, OutputInterface $output
	) {
		$users = $this->userManager->search('');

		foreach ($users as $user) {
			$this->hasBeenInterrupted();

			$output->writeln(' USER: ' . $user->getUID());
			$this->indexService->indexProviderContentFromUser($provider, $user->getUID(), $this);
		}

		$this->providerService->setProviderAsIndexed($provider, true);

	}

}



