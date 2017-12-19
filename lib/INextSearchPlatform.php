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
use OCA\FullNextSearch\Model\Index;
use OCA\FullNextSearch\Model\IndexDocument;
use OCA\FullNextSearch\Model\Runner;
use OCA\FullNextSearch\Model\SearchResult;

interface INextSearchPlatform {

	/**
	 * must returns a unique Id
	 *
	 * @return string
	 */
	public function getId();


	public function getName();


	public function getClient();


	/**
	 * @param Runner $runner
	 */
	public function setRunner(Runner $runner);

	/**
	 * Load the search platform
	 */
	public function loadPlatform();


	/** no used yet */
	public function testPlatform();


	/**
	 * Init an index regarding a provider
	 *
	 * @param INextSearchProvider $provider
	 */
	public function initializeIndex(INextSearchProvider $provider);


	/**
	 * Reset the indexes
	 *
	 * @param INextSearchProvider $provider
	 */
	public function removeIndex($provider);


	/**
	 * $command can be null. instanceof ExtendedBase if the method is called from CLI.
	 * Use it to echo whatever and intercept ^C
	 *
	 * @param INextSearchProvider $provider
	 * @param IndexDocument[] $documents
	 *
	 * @return Index[]
	 */
	public function indexDocuments(INextSearchProvider $provider, $documents);


	/**
	 * @param INextSearchProvider $provider
	 * @param IndexDocument $document
	 *
	 * @return Index
	 */
	public function indexDocument(INextSearchProvider $provider, IndexDocument $document);


	/**
	 * @param INextSearchProvider $provider
	 * @param DocumentAccess $access
	 * @param string $string
	 *
	 * @return SearchResult
	 */
	public function searchDocuments(INextSearchProvider $provider, DocumentAccess $access, $string);

}