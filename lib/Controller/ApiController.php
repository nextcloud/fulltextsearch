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
use OCA\FullTextSearch\Service\SearchService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class ApiController extends Controller {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private SearchService $searchService,
		private LoggerInterface $logger
	) {
		parent::__construct(Application::APP_ID, $request);
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
			$data = [
				'result' => $this->searchService->search($user->getUID(), $request),
				'status' => 1
			];

			return new DataResponse($data, Http::STATUS_OK);
		} catch (Exception $e) {
			$data = ['status' => -1, 'message' => $e->getMessage()];
			$this->logger->warning('issue while searchDocuments', ['exception' => $e, 'request' => $request]);

			return new DataResponse($data, Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
