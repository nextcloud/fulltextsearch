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
use OC\FullTextSearch\Model\DocumentAccess;
use OC\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Provider\TestProvider;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\IIndexOptions;


/**
 * Class TestService
 *
 * @package OCA\FullTextSearch\Service
 */
class TestService {

	const DOCUMENT_USER1 = 'user1';
	const DOCUMENT_USER2 = 'user2';
	const DOCUMENT_USER3 = 'user3';
	const DOCUMENT_NOTUSER = 'notuser';

	const DOCUMENT_GROUP1 = 'group_1';
	const DOCUMENT_GROUP2 = 'group_2';
	const DOCUMENT_NOTGROUP = 'group_3';

	const DOCUMENT_TYPE_LICENSE = 'license';
	const DOCUMENT_TYPE_SIMPLE = 'simple';

	const DOCUMENT_INDEXING_OPTION = 'indexing';
	const DOCUMENT_INDEXING_ACCESS = 'access';

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
	 * @param IIndexOptions $options
	 *
	 * @return IIndexDocument
	 */
	public function generateIndexDocumentContentLicense(IIndexOptions $options): IIndexDocument {
		$indexDocument = $this->generateIndexDocument(self::DOCUMENT_TYPE_LICENSE);

		$content = file_get_contents(__DIR__ . '/../../LICENSE');
		$indexDocument->setContent($content);

		if ($options === null) {
			return $indexDocument;
		}

		if ($options->getOption(self::DOCUMENT_INDEXING_OPTION, '')
			=== self::DOCUMENT_INDEXING_ACCESS) {
			$indexDocument->getAccess()
						  ->setGroups([self::DOCUMENT_GROUP1, self::DOCUMENT_GROUP2]);
			$indexDocument->getAccess()
						  ->setUsers([self::DOCUMENT_USER2, self::DOCUMENT_USER3]);
		}

		return $indexDocument;
	}


	/**
	 * @param IIndexOptions $options
	 *
	 * @return IIndexDocument
	 */
	public function generateIndexDocumentSimple(IIndexOptions $options): IIndexDocument {

		$indexDocument = $this->generateIndexDocument(self::DOCUMENT_TYPE_SIMPLE);
		$indexDocument->setContent('testing document is a simple test');

		return $indexDocument;
	}


	/**
	 * @param IIndexDocument $origIndex
	 * @param IIndexDocument $compareIndex
	 *
	 * @throws Exception
	 */
	public function compareIndexDocument(IIndexDocument $origIndex, IIndexDocument $compareIndex) {
		if ($origIndex->getAccess()
					  ->getOwnerId() !== $compareIndex->getAccess()
													  ->getOwnerId()) {
			throw new Exception('issue with AccessDocument');
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
				throw new Exception($method . '() orig:' . $orig . ' compare:' . $compare);
			}
		}
	}


	/**
	 * @param string $documentType
	 *
	 * @return IIndexDocument
	 */
	private function generateIndexDocument(string $documentType): IIndexDocument {
		$indexDocument = new IndexDocument(TestProvider::TEST_PROVIDER_ID, $documentType);

		$access = new DocumentAccess(self::DOCUMENT_USER1);
		$indexDocument->setAccess($access);

		return $indexDocument;
	}

}
