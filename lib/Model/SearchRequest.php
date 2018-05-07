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

namespace OCA\FullTextSearch\Model;

use OCA\FullTextSearch\Service\MiscService;

class SearchRequest implements \JsonSerializable {

	/** @var array */
	private $providers;

	/** @var string */
	private $search;

	/** @var int */
	private $page = 1;

	/** @var int */
	private $size = 10;

	/** @var string */
	private $author;

	/** @var array */
	private $tags;

	/** @var array */
	private $options;

	/** @var array */
	private $parts = [];

	/** @var array */
	private $wildcardQueries = [];

	/** @var array */
	private $wildcardFilters = [];

	/** @var array */
	private $regexFilters = [];


	/**
	 * SearchRequest constructor.
	 */
	public function __construct() {
	}


	/**
	 * @return array
	 */
	public function getProviders() {
		return $this->providers;
	}

	/**
	 * @param string|array $providers
	 */
	public function setProviders($providers) {
		if (!is_array($providers)) {
			$providers = [$providers];
		}

		$this->providers = $providers;
	}


	/**
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * @param string $author
	 */
	public function setAuthor($author) {
		$this->author = $author;
	}


	/**
	 * @return string
	 */
	public function getSearch() {
		return $this->search;
	}

	/**
	 * @param string $search
	 */
	public function setSearch($search) {
		$this->search = $search;
	}

	public function cleanSearch() {
		$this->search = trim(str_replace('  ', ' ', $this->search));
	}


	/**
	 * @return int
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * @param int $page
	 */
	public function setPage($page) {
		if ($page < 1) {
			$page = 1;
		}

		$this->page = $page;
	}


	/**
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * @param int $size
	 */
	public function setSize($size) {
		$this->size = $size;
	}


	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * @param string $option
	 *
	 * @return mixed|string
	 */
	public function getOption($option) {
		if (array_key_exists($option, $this->options)) {
			return $this->options[$option];
		}

		return '';
	}


	/**
	 * @param array $parts
	 *
	 * @return $this
	 */
	public function setParts($parts) {
		$this->parts = $parts;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getParts() {
		return $this->parts;
	}


	/**
	 * @param string $tag
	 */
	public function addTag($tag) {
		$this->tags[] = $tag;
	}

	/**
	 * @return array
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * @param array $tags
	 */
	public function setTags($tags) {
		$this->tags = $tags;
	}


	/**
	 * @param array $query
	 *
	 * @return $this
	 */
	public function addWildcardQuery($query) {
		$this->addWildcardQueries([$query]);

		return $this;
	}

	/**
	 * @param array $query
	 *
	 * @return $this
	 */
	public function addWildcardQueries($query) {
		array_push($this->wildcardQueries, $query);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getWildcardQueries() {
		return $this->wildcardQueries;
	}


	/**
	 * @param array $filter
	 *
	 * @return $this
	 */
	public function addWildcardFilter($filter) {
		$this->addWildcardFilters([$filter]);

		return $this;
	}

	/**
	 * @param array $filters
	 *
	 * @return $this
	 */
	public function addWildcardFilters($filters) {
		array_push($this->wildcardFilters, $filters);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getWildcardFilters() {
		return $this->wildcardFilters;
	}


	/**
	 * @param array $filter
	 *
	 * @return $this
	 */
	public function addRegexFilter($filter) {
		$this->addRegexFilters([$filter]);

		return $this;
	}

	/**
	 * @param array $filters
	 *
	 * @return $this
	 */
	public function addRegexFilters($filters) {
		array_push($this->regexFilters, $filters);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRegexFilters() {
		return $this->regexFilters;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'providers' => $this->getProviders(),
			'author'    => $this->getAuthor(),
			'search'    => $this->getSearch(),
			'page'      => $this->getPage(),
			'size'      => $this->getSize(),
			'parts'     => $this->getParts(),
			'options'   => $this->getOptions(),
			'tags'      => $this->getTags()
		];
	}


	/**
	 * @param string $json
	 *
	 * @return SearchRequest
	 */
	public static function fromJSON($json) {
		return self::fromArray(json_decode($json, true));
	}

	/**
	 * @param array $arr
	 *
	 * @return SearchRequest
	 */
	public static function fromArray($arr) {
		$request = new SearchRequest();
		$request->setProviders($arr['providers']);
		$request->setAuthor(MiscService::get($arr, 'author', ''));
		$request->setSearch(MiscService::get($arr, 'search', ''));
		$request->setPage(MiscService::get($arr, 'page', 0));
		$request->setParts(MiscService::get($arr, 'parts', []));
		$request->setSize(MiscService::get($arr, 'size', 10));
		$request->setOptions(MiscService::get($arr, 'options', []));
		$request->setTags(MiscService::get($arr, 'tags', []));

		return $request;
	}


}