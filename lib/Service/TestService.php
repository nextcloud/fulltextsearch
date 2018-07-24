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
use OCP\IConfig;
use OCP\PreConditionNotMetException;
use OCP\Util;

class TestService {

	const DOCUMENT_TEST_INDEX1 = 1;
	const DOCUMENT_TEST_INDEX2 = 2;
	const DOCUMENT_TEST_INDEX3 = 3;

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
	 * @param int $documentType
	 *
	 * @return IndexDocument
	 */
	public function generateIndexDocuments($documentType) {
		$indexDocument = new IndexDocument('test', 'test_' . $documentType);
		$indexDocument->setContent('This is the content');

		$access = new DocumentAccess('user');
		$indexDocument->setAccess($access);
		return $indexDocument;
	}
}
