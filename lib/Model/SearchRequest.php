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
	private $tags = [];

	/** @var array */
	public $metaTags = [];

	/** @var array */
	public $subTags = [];

	/** @var array */
	private $options;

	/** @var array */
	private $parts = [];

	/** @var array */
	private $fields = [];

	/** @var array */
	private $limitFields = [];

	/** @var array */
	private $wildcardFields = [];

//	/** @var array */
//	private $wildcardQueries = [];

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


	/**
	 *
	 */
	public function cleanSearch() {
		$search = trim(str_replace('  ', ' ', $this->getSearch()));

		preg_match_all('/[^?]"(?:\\\\.|[^\\\\"])*"|\S+/', " $search ", $words);
		$searchItems = [];
		foreach ($words[0] as $word) {
			if ($this->searchQueryOptions($word)) {
				continue;
			}

			$searchItems[] = $word;
		}

		$this->setSearch(implode(" ", $searchItems));
	}


	/**
	 * @param $word
	 *
	 * @return bool
	 */
	private function searchQueryOptions($word) {
		if (($pos = strpos($word, ':')) === false) {
			return false;
		}

		list($kw, $value) = explode(':', $word, 2);

		$options = ['is', 'show'];
		if (in_array($kw, $options)) {
			$this->addOption($kw . '_' . $value, '1');

			return true;
		}

		$valuedOptions = ['in'];
		if (in_array($kw, $valuedOptions)) {
			$this->addMultipleOption($kw, $value);

			return true;
		}

		return false;
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
	 * @param $key
	 * @param $value
	 *
	 * @return $this
	 */
	public function addOption($key, $value) {
		$this->options[$key] = $value;

		return $this;
	}

	/**
	 * @param $key
	 * @param $value
	 *
	 * @return $this
	 */
	public function addMultipleOption($key, $value) {
		if (!array_key_exists($key, $this->options)) {
			$this->options[$key] = [];
		}

		$this->options[$key][] = $value;

		return $this;
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
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param array $fields
	 *
	 * @return $this
	 */
	public function setFields($fields) {
		$this->fields = $fields;

		return $this;
	}


	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function limitToField($field) {
		array_push($this->limitFields, $field);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getLimitFields() {
		return $this->limitFields;
	}


	/**
	 * @param $field
	 *
	 * @return $this
	 */
	public function addField($field) {
		$this->fields[] = $field;

		return $this;
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
	 * @param array $tags
	 *
	 * @return $this
	 */
	public function setMetaTags($tags) {
		$this->metaTags = $tags;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMetaTags() {
		return $this->metaTags;
	}

	/**
	 * @param string $tags
	 *
	 * @return $this
	 */
	public function addMetaTag($tags) {
		$this->metaTags[] = $tags;

		return $this;
	}


	/**
	 * @param array $tags
	 *
	 * @return $this
	 */
	public function setSubTags($tags) {
		$this->subTags = $tags;

		return $this;
	}

	/**
	 * @param bool $formatted
	 *
	 * @return array
	 */
	public function getSubTags($formatted = false) {
		if ($formatted === false) {
			return $this->subTags;
		}

		$subTags = [];
		$ak = array_keys($this->subTags);
		foreach ($ak as $source) {
			$tags = $this->subTags[$source];
			foreach ($tags as $tag) {
				$subTags[] = $source . '_' . $tag;
			}
		}

		return $subTags;
	}

	/**
	 * @param string $source
	 * @param string $tag
	 *
	 * @return $this
	 */
	public function addSubTag($source, $tag) {
		if (!array_key_exists($source, $this->subTags)) {
			$this->subTags[$source] = [];
		}

		$this->subTags[$source][] = $tag;

		return $this;
	}


	/**
	 * @param string $field
	 *
	 * @return $this
	 */
	public function addWildcardField($field) {
		$this->wildcardFields[] = $field;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getWildcardFields() {
		return $this->wildcardFields;
	}

//
//	/**
//	 * @param array $query
//	 *
//	 * @return $this
//	 */
//	public function addWildcardQuery($query) {
//		$this->addWildcardQueries([$query]);
//
//		return $this;
//	}
//
//	/**
//	 * @param array $query
//	 *
//	 * @return $this
//	 */
//	public function addWildcardQueries($query) {
//		array_push($this->wildcardQueries, $query);
//
//		return $this;
//	}
//
//	/**
//	 * @return array
//	 */
//	public function getWildcardQueries() {
//		return $this->wildcardQueries;
//	}


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
			'metatags'  => $this->getMetaTags(),
			'subtags'   => $this->getSubTags(),
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
		$request->setAuthor(MiscService::get('author', $arr, ''));
		$request->setSearch(MiscService::get('search', $arr, ''));
		$request->setPage(MiscService::get('page', $arr, 0));
		$request->setParts(MiscService::get('parts', $arr, []));
		$request->setSize(MiscService::get('size', $arr, 10));
		$request->setOptions(MiscService::get('options', $arr, []));
		$request->setMetaTags(MiscService::get('metatags', $arr, []));
		$request->setSubTags(MiscService::get('subtags', $arr, []));
		$request->setTags(MiscService::get('tags', $arr, []));

		return $request;
	}


}