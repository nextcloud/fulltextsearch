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

namespace OCA\FullTextSearch;


use OCA\FullTextSearch\Model\DocumentAccess;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Model\SearchResult;

interface IFullTextSearchPlatform {

	/**
	 * must returns a unique Id
	 *
	 * @return string
	 */
	public function getId();


	/**
	 * @return string
	 */
	public function getName();


	/**
	 * @return string
	 */
	public function getVersion();


	/**
	 * @return array
	 */
	public function getConfiguration();


	/**
	 * @param Runner $runner
	 */
	public function setRunner(Runner $runner);


	/**
	 * Load the search platform
	 */
	public function loadPlatform();


	/**
	 * test the search platform
	 */
	public function testPlatform();


	/**
	 * Init an index regarding a provider
	 */
	public function initializeIndex();


	/**
	 * Reset the indexes
	 */
	public function resetIndex();


	/**
	 * Reset indexes
	 *
	 * @param Index[] $indexes
	 */
	public function deleteIndexes($indexes);


	/**
	 * $command can be null. instanceof ExtendedBase if the method is called from CLI.
	 * Use it to echo whatever and intercept ^C
	 *
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument[] $documents
	 *
	 * @return Index[]
	 */
	public function indexDocuments(IFullTextSearchProvider $provider, $documents);


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param IndexDocument $document
	 *
	 * @return Index
	 */
	public function indexDocument(IFullTextSearchProvider $provider, IndexDocument $document);


	/**
	 * @param IFullTextSearchProvider $provider
	 * @param DocumentAccess $access
	 * @param SearchRequest $request
	 *
	 * @return SearchResult
	 */
	public function searchDocuments(
		IFullTextSearchProvider $provider, DocumentAccess $access, SearchRequest $request
	);

}