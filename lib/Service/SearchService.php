<?php
declare(strict_types=1);


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
use OC\FullTextSearch\Model\DocumentAccess;
use OC\User\NoUserException;
use OCA\Circles\Api\v1\Circles;
use OCA\FullTextSearch\Exceptions\EmptySearchException;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Model\SearchResult;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\IFullTextSearchProvider;
use OCP\FullTextSearch\Model\IDocumentAccess;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\FullTextSearch\Service\ISearchService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;


/**
 * Class SearchService
 *
 * @package OCA\FullTextSearch\Service
 */
class SearchService implements ISearchService {


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
	 * SearchService constructor.
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
		$userId, AppManager $appManager, IUserManager $userManager,
		IGroupManager $groupManager,
		ConfigService $configService, ProviderService $providerService,
		PlatformService $platformService,
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
	 * @param array $request
	 *
	 * @return ISearchRequest
	 */
	public function generateSearchRequest(array $request): ISearchRequest {
		$searchRequest = new SearchRequest();
		$searchRequest->importFromArray($request);

		return $searchRequest;
	}


	/**
	 * @param string $userId
	 * @param ISearchRequest $request
	 *
	 * @return ISearchResult[]
	 * @throws EmptySearchException
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function search(string $userId, ISearchRequest $request): array {
		$this->searchRequestCannotBeEmpty($request);

		if ($userId === '') {
			$userId = $this->userId;
		}

		$user = $this->userManager->get($userId);
		if ($user === null) {
			throw new NoUserException('User does not exist');
		}

		/** @var $request SearchRequest */
		$request->setAuthor($user->getUID());
		$request->cleanSearch();

		$providers = $this->providerService->getFilteredProviders($request->getProviders());
		$wrapper = $this->platformService->getPlatform();
		$platform = $wrapper->getPlatform();

		$access = $this->getDocumentAccessFromUser($user);

		return $this->searchFromProviders($platform, $providers, $access, $request);
	}


	/**
	 * @param ISearchRequest $request
	 *
	 * @throws EmptySearchException
	 */
	private function searchRequestCannotBeEmpty(ISearchRequest $request) {
		if ($request === null || (strlen($request->getSearch()) < 1 && !$request->isEmptySearch())) {
			throw new EmptySearchException('search cannot be empty');
		}
	}


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param IDocumentAccess $access
	 * @param IFullTextSearchProvider[] $providers
	 * @param SearchRequest $request
	 *
	 * @return ISearchResult[]
	 */
	private function searchFromProviders(
		IFullTextSearchPlatform $platform, array $providers, IDocumentAccess $access,
		SearchRequest $request
	): array {
		$result = [];
		foreach ($providers as $provider) {
			$provider->improveSearchRequest($request);

			$searchResult = new SearchResult($request);
			$searchResult->setProvider($provider);
			$searchResult->setPlatform($platform);

			$platform->searchRequest($searchResult, $access);
			$provider->improveSearchResult($searchResult);

			$result[] = $searchResult;
		}

		return $result;
	}


	/**
	 * @param IUser $user
	 *
	 * @return IDocumentAccess
	 */
	private function getDocumentAccessFromUser(IUser $user): IDocumentAccess {
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
