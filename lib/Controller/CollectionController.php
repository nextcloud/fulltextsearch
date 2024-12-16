<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Controller;


use OC\ForbiddenException;
use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\CollectionArgumentException;
use OCA\FullTextSearch\Service\CollectionService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;

class CollectionController extends OCSController {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private CollectionService $collectionService
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $collection
	 * @param int $length
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getQueue(string $collection, int $length = 0): DataResponse {
		try {
			$this->collectionService->confirmCollection($collection);
			$this->confirmAccess($collection);

			return new DataResponse($this->collectionService->getQueue($collection, $length));
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}


	/**
	 * @NoAdminRequired
	 *
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
			$this->confirmAccess($collection);

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
	 * @NoAdminRequired
	 *
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
			$this->confirmAccess($collection);

			$this->collectionService->setAsDone($collection, $providerId, $documentId);

			return new DataResponse([]);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * confirm that current session have access to collection
	 *
	 * @param string $collection
	 *
	 * @return void
	 * @throws ForbiddenException
	 */
	private function confirmAccess(string $collection): void {
		$currentUserId = $this->userSession->getUser()->getUID();
		if ($this->groupManager->isAdmin($currentUserId)) {
			return;
		}

		try {
			if ($this->collectionService->getLinkedAccount($collection) === $currentUserId) {
				return;
			}
		} catch (CollectionArgumentException) {
		}

		throw new ForbiddenException('API access not allowed');
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

