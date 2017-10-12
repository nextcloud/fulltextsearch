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

namespace OCA\FullNextSearch;


use OCA\FullNextSearch\Model\DocumentAccess;
use OCA\FullNextSearch\Model\DocumentIndex;
use OCA\FullNextSearch\Model\ExtendedBase;
use OCA\FullNextSearch\Model\SearchDocument;
use OCA\FullNextSearch\Model\SearchResult;

interface INextSearchPlatform {

	/**
	 * must returns a unique Id
	 *
	 * @return string
	 */
	public function getId();


	public function getName();


	/**
	 * Load the search provider
	 */
	public function loadPlatform();


	/** no used yet */
	public function testPlatform();


	/**
	 *
	 */
	public function initPlatform();


	/**
	 * @param INextSearchProvider $provider
	 */
	public function resetPlatform($provider);


	/**
	 * $command can be null. instanceof ExtendedBase if the method is called from CLI.
	 * Use it to echo whatever and intercept ^C
	 *
	 * @param INextSearchProvider $provider
	 * @param SearchDocument[] $documents
	 * @param ExtendedBase|null $command
	 *
	 * @return DocumentIndex[]
	 */
	public function indexDocuments(INextSearchProvider $provider, $documents, $command);


	/**
	 * @param INextSearchProvider $provider
	 * @param SearchDocument $document
	 *
	 * @return DocumentIndex
	 */
	public function indexDocument(INextSearchProvider $provider, SearchDocument $document);


	/**
	 * @param INextSearchProvider $provider
	 * @param DocumentAccess $access
	 * @param string $string
	 *
	 * @return SearchResult
	 */
	public function searchDocuments(INextSearchProvider $provider, DocumentAccess $access, $string);

}