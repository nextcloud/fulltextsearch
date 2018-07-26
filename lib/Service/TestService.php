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

use OCA\FullTextSearch\AppInfo\Application;
use OCA\FullTextSearch\Exceptions\ProviderOptionsDoesNotExistException;
use OCA\FullTextSearch\Model\DocumentAccess;
use OCA\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Provider\TestProvider;
use OCP\IConfig;
use OCP\PreConditionNotMetException;
use OCP\Util;

class TestService {

	const DOCUMENT_USER = 'user';

	const DOCUMENT_TEST_LICENSE = 'license';
	const DOCUMENT_TEST_SIMPLE = 'simple';
	const DOCUMENT_TEST_ACCESS = 'access';

	const LICENSE_HASH = '108322602bb857915803a84e23a2cc2f';

	/** @var MiscService */
	private $miscService;


	/**
	 * TestService constructor.
	 *
	 * @param MiscService $miscService
	 */
	public function __construct(MiscService $miscService) {
		$this->miscService = $miscService;
	}


	/**
	 * @return IndexDocument
	 */
	public function generateIndexDocumentContentLicense() {
		$indexDocument = $this->generateIndexDocument(self::DOCUMENT_TEST_LICENSE);

		$content = file_get_contents(__DIR__ . '/../../LICENSE');
		$indexDocument->setContent($content);

		return $indexDocument;
	}


	/**
	 * @return IndexDocument
	 */
	public function generateIndexDocumentSimple() {

		$indexDocument = $this->generateIndexDocument(self::DOCUMENT_TEST_SIMPLE);
		$indexDocument->setContent('This is a test');

		return $indexDocument;
	}


	/**
	 * @param IndexDocument $origIndex
	 * @param IndexDocument $compareIndex
	 *
	 * @throws \Exception
	 */
	public function compareIndexDocument(IndexDocument $origIndex, IndexDocument $compareIndex) {
		if ($origIndex->getAccess()
					  ->getOwnerId() !== $compareIndex->getAccess()
													  ->getOwnerId()) {
			throw new \Exception('issue with AccessDocument');
		}

		$methods = [
			'getId',
			'getProviderId',
			'getTitle',
			'getSource'
		];

		foreach ($methods as $method) {
			$orig = call_user_func([$origIndex, $method]);
			$compare = call_user_func([$compareIndex, $method]);
			if ($orig !== $compare) {
				throw new \Exception($method . '() orig:' . $orig . ' compare:' . $compare);

			};
		}
	}


	/**
	 * @param string $documentType
	 *
	 * @return IndexDocument
	 */
	private function generateIndexDocument($documentType) {
		$indexDocument = new IndexDocument(TestProvider::TEST_PROVIDER_ID, $documentType);

		$access = new DocumentAccess(self::DOCUMENT_USER);
		$indexDocument->setAccess($access);

		return $indexDocument;
	}

}
