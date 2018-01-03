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

namespace OCA\FullNextSearch\Controller;

use Exception;
use OCA\FullNextSearch\AppInfo\Application;
use OCA\FullNextSearch\Model\SearchRequest;
use OCA\FullNextSearch\Model\SearchResult;
use OCA\FullNextSearch\Service\MiscService;
use OCA\FullNextSearch\Service\SearchService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ApiController extends Controller {

	/** @var SearchService */
	private $searchService;

	/** @var MiscService */
	private $miscService;


	/**
	 * NavigationController constructor.
	 *
	 * @param IRequest $request
	 * @param SearchService $searchService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IRequest $request, SearchService $searchService, MiscService $miscService
	) {
		parent::__construct(Application::APP_NAME, $request);
		$this->searchService = $searchService;
		$this->miscService = $miscService;
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $providerId
	 * @param string $request
	 *
	 * @return DataResponse
	 */
	public function search($providerId, $request) {
		return $this->searchDocuments($providerId, SearchRequest::fromJSON($request));
	}


	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $providerId
	 * @param string $request
	 *
	 * @return DataResponse
	 */
	public function searchFromRemote($providerId, $request) {
		return $this->searchDocuments($providerId, SearchRequest::fromJSON($request));
	}


	/**
	 * @param string $providerId
	 * @param SearchRequest $request
	 *
	 * @return DataResponse
	 */
	private function searchDocuments($providerId, SearchRequest $request) {
		try {
			$result = $this->searchService->search($providerId, null, $request);
//			$meta = $this->generateMeta($result);

			return $this->success(
				['request' => $request, 'provider' => $providerId, 'result' => $result]
			);
		} catch (Exception $e) {
			return $this->fail(
				['request' => $request, 'provider' => $providerId, 'error' => $e->getMessage()]
			);
		}
	}
//
//
//	/**
//	 * @param SearchResult[] $result
//	 *
//	 * @return array<string,integer>
//	 */
//	private function generateMeta($result) {
//
//		$meta = [
//			'size'     => 0,
//			'time'     => 0,
//			'total'    => 0,
//			'maxScore' => 0,
//			'timedOut' => false
//		];
//
//		foreach ($result as $searchResult) {
//			$meta['size'] += $searchResult->getSize();
//			$meta['time'] += $searchResult->getTime();
//			$meta['total'] += $searchResult->getTotal();
//			$meta['maxScore'] += $searchResult->getMaxScore();
//			if ($searchResult->isTimedOut()) {
//				$meta['timedOut'] = true;
//			}
//		}
//
//		return $meta;
//	}
//

	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	protected function fail($data) {
		$this->miscService->log(json_encode($data));

		return new DataResponse(
			array_merge($data, array('status' => 0)),
			Http::STATUS_NON_AUTHORATIVE_INFORMATION
		);
	}


	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	protected function success($data) {
		return new DataResponse(
			array_merge($data, array('status' => 1)),
			Http::STATUS_CREATED
		);
	}

}