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

use OC\User\NoUserException;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Model\Runner;
use OCA\FullTextSearch\Model\SearchRequest;
use OCA\FullTextSearch\Model\SearchResult;
use OCP\AppFramework\Http\TemplateResponse;

interface IFullTextSearchProvider {


	/**
	 * return a unique Id of the Provider
	 *
	 * @return string
	 */
	public function getId();


	/**
	 * return a appId of the Provider
	 *
	 * @return string
	 */
	public function getAppId();


	/**
	 * return a display name of the Provider
	 *
	 * @return string
	 */
	public function getName();


	/**
	 * return a display version of the Provider
	 *
	 * @return string
	 */
	public function getVersion();


	/**
	 * @return array
	 */
	public function getConfiguration();


	/**
	 * @return array|string
	 */
	public function getOptionsTemplate();


	/**
	 * Called when loading the provider
	 */
	public function loadProvider();


	/**
	 * @param Runner $runner
	 */
	public function setRunner(Runner $runner);


	/**
	 * returns all indexable document for a user.
	 * There is no need to fill the document with content at this point.
	 *
	 * @param string $userId
	 *
	 * @return IndexDocument[]
	 */
	public function generateIndexableDocuments($userId);


	/**
	 * fill a chunk of documents with more content, prior to index.
	 *
	 * @param IndexDocument[] $chunk
	 *
	 * @return IndexDocument[]
	 */
	public function fillIndexDocuments($chunk);


	/**
	 * @param IndexDocument $document
	 *
	 * @return bool
	 */
	public function isDocumentUpToDate($document);


	/**
	 * update a document regarding the current Index' status
	 *
	 * @param Index $index
	 *
	 * @return IndexDocument
	 * @throws NoUserException
	 */
	public function updateDocument(Index $index);


	/**
	 * @param IFullTextSearchPlatform $platform
	 */
	public function onInitializingIndex(IFullTextSearchPlatform $platform);


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param array $arr
	 */
	public function onIndexingDocument(IFullTextSearchPlatform $platform, &$arr);


	/**
	 * @param IFullTextSearchPlatform $platform
	 */
	public function onResettingIndex(IFullTextSearchPlatform $platform);


	/**
	 * @param IFullTextSearchPlatform $platform
	 * @param SearchRequest $request
	 * @param array $arr
	 *
	 * @return array
	 */
	public function onSearchingQuery(
		IFullTextSearchPlatform $platform, SearchRequest $request, &$arr
	);


	/**
	 * before a search, improve the request
	 *
	 * @param SearchRequest $searchRequest
	 */
	public function improveSearchRequest(SearchRequest $searchRequest);


	/**
	 * after a search, improve the result
	 *
	 * @param SearchResult $searchResult
	 */
	public function improveSearchResult(SearchResult $searchResult);


	/**
	 * not used yet.
	 */
	public function unloadProvider();

}