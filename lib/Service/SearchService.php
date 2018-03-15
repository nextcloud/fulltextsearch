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

namespace OCA\FullTextSearch\Service;

use Exception;
use OC\App\AppManager;
use OCA\Circles\Api\v1\Circles;
use OCA\FullTextSearch\Exceptions\EmptySearchException;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\IFullTextSearchPlatform;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\DocumentAccess;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Model\SearchResult;
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
	public function __construct(
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


	/**
	 * @param string $userId
	 * @param SearchRequest $request
	 *
	 * @return SearchResult[]
	 * @throws EmptySearchException
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function search($userId, SearchRequest $request) {

		$this->searchCannotBeEmpty($request);

		if ($userId === null) {
			$userId = $this->userId;
		}

		$user = $this->userManager->get($userId);
		$request->setAuthor($user->getUID());
		$request->cleanSearch();

		$providers = $this->providerService->getFilteredProviders($request->getProviders());
		$platform = $this->platformService->getPlatform();

		$access = $this->getDocumentAccessFromUser($user);
		$result = $this->searchFromProviders($platform, $providers, $access, $request);

		foreach ($result as $searchResult) {
			$searchResult->setPlatform($platform);
			$searchResult->setRequest($request);
		}

		return $result;
	}


	/**
	 * @param string $search
	 *
	 * @throws EmptySearchException
	 */
	private function searchCannotBeEmpty($search) {
		if ($search === null || $search === '') {
			throw new EmptySearchException('search cannot be empty');
		}
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param DocumentAccess $access
	 * @param IFullTextSearchProvider[] $providers
	 * @param SearchRequest $request
	 *
	 * @return SearchResult[]
	 */
	private function searchFromProviders(
		IFullTextSearchPlatform $platform, array $providers, DocumentAccess $access, SearchRequest $request
	) {
		$result = [];
		foreach ($providers AS $provider) {
			$provider->improveSearchRequest($request);
			$searchResult = $platform->searchDocuments($provider, $access, $request);
			$searchResult->setProvider($provider);
			$searchResult->setPlatform($platform);

			$provider->improveSearchResult($searchResult);
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

		$rights->setViewerId($user->getUID());
		$rights->setGroups($this->groupManager->getUserGroupIds($user));

		if ($this->appManager->isEnabledForUser('circles', $user)) {
			try {
				$rights->setCircles(Circles::joinedCircleIds($user->getUID()));
			} catch (Exception $e) {
				$this->miscService->log('Circles is set as enabled but: ' . $e->getMessage());
			}
		}

		return $rights;
	}


}