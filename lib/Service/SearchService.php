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

namespace OCA\FullNextSearch\Service;

use OC\App\AppManager;
use OCA\Circles\Api\v1\Circles;
use OCA\FullNextSearch\Model\DocumentAccess;
use OCA\FullNextSearch\Model\SearchResult;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;


class SearchService {

	/** @var string */
	private $userId;

	/** @var AppManager */
	private $appManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ConfigService */
	private $configService;

	/** @var ProviderService */
	private $providerService;

	/** @var PlatformService */
	private $platformService;

	/** @var MiscService */
	private $miscService;


	/**
	 * IndexService constructor.
	 *
	 * @param string $userId
	 * @param AppManager $appManager
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param ConfigService $configService
	 * @param ProviderService $providerService
	 * @param PlatformService $platformService
	 * @param MiscService $miscService
	 */
	function __construct(
		$userId, AppManager $appManager, IUserManager $userManager, IGroupManager $groupManager,
		ConfigService $configService, ProviderService $providerService, PlatformService $platformService,
		MiscService $miscService
	) {
		$this->userId = $userId;
		$this->appManager = $appManager;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->configService = $configService;
		$this->providerService = $providerService;
		$this->platformService = $platformService;
		$this->miscService = $miscService;
	}

//				echo memory_get_usage() . "\n";


	/**
	 * @param string $providerId
	 * @param string $userId
	 * @param string $search
	 *
	 * @return SearchResult[]
	 */
	public function search($providerId, $userId, $search) {

		if ($userId === null) {
			$userId = $this->userId;
		}

		$providers = $this->providerService->getFilteredProviders($providerId);
		$platform = $this->platformService->getPlatform();

		$user = $this->userManager->get($userId);
		$access = $this->getDocumentAccessFromUser($user);
		$result = [];
		foreach ($providers AS $provider) {
			$searchResult = $platform->search($provider, $access, $search);

			$provider->parseSearchResult($searchResult);
			if (sizeof($searchResult->getDocuments()) > 0) {
				$result[] = $searchResult;
			}
		}

		return $result;
	}


	/**
	 * @param IUser $user
	 *
	 * @return DocumentAccess
	 */
	private function getDocumentAccessFromUser(IUser $user) {
		$rights = new DocumentAccess();

		$rights->setViewer($user->getUID());
		$rights->setGroups($this->groupManager->getUserGroupIds($user));

		if ($this->appManager->isEnabledForUser('circles', $user)) {
			try {
				$rights->setCircles(Circles::joinedCircleIds($user->getUID()));
			} catch (\Exception $e) {
				$this->miscService->log('Circles is set as enabled but: ' . $e->getMessage());
			}
		}

		return $rights;
	}


}