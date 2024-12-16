<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2022
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


use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Service\CollectionService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\IRequest;


class CollectionController extends OCSController {


	/** @var CollectionService */
	private $collectionService;


	/**
	 * @param IRequest $request
	 * @param CollectionService $collectionService
	 */
	public function __construct(
		IRequest $request,
		CollectionService $collectionService
	) {
		parent::__construct(Application::APP_ID, $request);

		$this->collectionService = $collectionService;
	}


	/**
	 * @param string $collection
	 * @param int $length
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getQueue(string $collection, int $length = 0): DataResponse {
		try {
			$this->collectionService->confirmCollection($collection);

			return new DataResponse($this->collectionService->getQueue($collection, $length));
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @param string $collection
	 * @param int $length
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function resetCollection(string $collection): DataResponse {
		try {
			$this->collectionService->confirmCollection($collection);
			$this->confirmAccess($collection);

			$this->collectionService->resetCollection($collection);

			return new DataResponse(['done']);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $collection
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function indexDocument(string $collection, string $providerId, string $documentId): DataResponse {
		try {
			$this->collectionService->confirmCollection($collection);
			$document = $this->collectionService->getDocument(
				$collection,
				$providerId,
				$documentId
			);

			return new DataResponse($this->displayDocument($document));
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @param string $collection
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function updateStatusDone(
		string $collection,
		string $providerId,
		string $documentId
	): DataResponse {
		try {
			$this->collectionService->confirmCollection($collection);
			$this->collectionService->setAsDone($collection, $providerId, $documentId);

			return new DataResponse([]);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @param IIndexDocument $document
	 *
	 * @return array
	 */
	private function displayDocument(IIndexDocument $document): array {
		$display = [
			'id' => $document->getId(),
			'providerId' => $document->getProviderId(),
			'access' => $document->getAccess(),
			'index' => $document->getIndex(),
			'title' => $document->getTitle(),
			'link' => $document->getLink(),
			'parts' => $document->getParts(),
			'tags' => $document->getTags(),
			'metatags' => $document->getMetaTags(),
			'source' => $document->getSource(),
			'info' => $document->getInfoAll(),
			'hash' => $document->getHash(),
			'modifiedTime' => $document->getModifiedTime()
		];

		foreach ($document->getMore() as $k => $v) {
			$display[$k] = $v;
		}

		$display['content'] = $document->getContent();
		$display['isContentEncoded'] = $document->isContentEncoded();

		return json_decode(json_encode($display), true);
	}

}

