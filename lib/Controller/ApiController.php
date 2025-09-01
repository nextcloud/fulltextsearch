<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Controller;

use Exception;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Service\SearchService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;

class ApiController extends Controller {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private readonly IAppConfig $appConfig,
		private SearchService $searchService,
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

			return new DataResponse([
				'result' => $this->searchService->search($user->getUID(), $request),
				'status' => 1,
				'request' => $request,
				'version' => $this->appConfig->getAppValueString('installed_version')
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(
				[
					'status' => -1,
					'exception' => get_class($e),
					'message' => $e->getMessage(),
					'request' => $request,
					'version' => $this->appConfig->getAppValueString('installed_version')
				],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}
}

