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


namespace OCA\FullTextSearch\Controller;


use Exception;
use OC\AppFramework\Http;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\SearchService;
use OCA\FullTextSearch\Tools\Traits\TDeserialize;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;


/**
 * Class ApiController
 *
 * @package OCA\FullTextSearch\Controller
 */
class ApiController extends Controller {
	use TDeserialize;

	/** @var IUserSession */
	private $userSession;

	/** @var SearchService */
	private $searchService;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param IUserSession $userSession
	 * @param ConfigService $configService
	 * @param SearchService $searchService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, IUserSession $userSession, ConfigService $configService,
		SearchService $searchService,
		MiscService $miscService
	) {
		parent::__construct(Application::APP_ID, $request);

		$this->userSession = $userSession;
		$this->searchService = $searchService;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $request
	 *
	 * @return DataResponse
	 */
	public function search(string $request): DataResponse {
		return $this->searchDocuments(SearchRequest::fromJSON($request));
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $request
	 *
	 * @return DataResponse
	 */
	public function searchFromRemote(string $request): DataResponse {
		return $this->searchDocuments(SearchRequest::fromJSON($request));
	}


	/**
	 * @param SearchRequest $request
	 *
	 * @return DataResponse
	 */
	private function searchDocuments(SearchRequest $request): DataResponse {
		try {
			$user = $this->userSession->getUser();
			$result = $this->searchService->search($user->getUID(), $request);

			return new DataResponse(
				[
					'request' => $request,
					'version' => $this->configService->getAppValue('installed_version'),
					'result' => $result,
					'status' => 1
				]
			);
		} catch (Exception $e) {
			return new DataResponse(
				[
					'request' => $request,
					'version' => $this->configService->getAppValue('installed_version'),
					'status' => -1,
					'exception' => get_class($e),
					'message' => $e->getMessage()
				], Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}
}

