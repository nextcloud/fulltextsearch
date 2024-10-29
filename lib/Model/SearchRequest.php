<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchRequestSimpleQuery;


/**
 * Class SearchRequest
 *
 * @package OCA\FullTextSearch\Model
 */
class SearchRequest implements ISearchRequest, JsonSerializable {


	use TArrayTools;


	/** @var array */
	private $providers = [];

	/** @var string */
	private $search = '';

	/** @var bool */
	private $emptySearch = false;

	/** @var int */
	private $page = 1;

	/** @var int */
	private $size = 10;

	/** @var string */
	private $author = '';

	/** @var array */
	private $tags = [];

	/** @var array */
	public $metaTags = [];

	/** @var array */
	public $subTags = [];

	/** @var array */
	private $options = [];

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

	/** @var array */
	private $simpleQueries = [];


	/**
	 * SearchRequest constructor.
	 */
	public function __construct() {
	}


	/**
	 * @return array
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * @param array $providers
	 *
	 * @return ISearchRequest
	 */
	public function setProviders(array $providers): ISearchRequest {
		$this->providers = $providers;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getAuthor(): string {
		return $this->author;
	}

	/**
	 * @param string $author
	 *
	 * @return ISearchRequest
	 */
	public function setAuthor(string $author): ISearchRequest {
		$this->author = $author;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSearch(): string {
		return $this->search;
	}

	/**
	 * @param string $search
	 *
	 * @return ISearchRequest
	 */
	public function setSearch(string $search): ISearchRequest {
		$this->search = $search;

		return $this;
	}

	/**
	 * @param string $search
	 *
	 * @return ISearchRequest
	 */
	public function addSearch(string $search): ISearchRequest {
		$this->search .= ' ' . $search;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isEmptySearch(): bool {
		return $this->emptySearch;
	}

	/**
	 * @param bool $emptySearch
	 *
	 * @return ISearchRequest
	 */
	public function setEmptySearch(bool $emptySearch): ISearchRequest {
		$this->emptySearch = $emptySearch;

		return $this;
	}


	/**
	 *
	 */
	public function cleanSearch(): ISearchRequest {
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

		return $this;
	}


	/**
	 * @param string $word
	 *
	 * @return bool
	 */
	private function searchQueryOptions(string $word): bool {
		if (($pos = strpos($word, ':')) === false || $pos === 0) {
			return false;
		}

		list($kw, $value) = explode(':', $word, 2);

		$options = ['is', 'show'];
		if (in_array($kw, $options)) {
			$this->addOption($kw . '_' . $value, '1');

			return true;
		}

		$valuedOptions = ['in', 'meta'];
		if (in_array($kw, $valuedOptions)) {
			$this->addMultipleOption($kw, $value);

			return true;
		}

		$valuedSubOptions = ['and'];
		if (in_array($kw, $valuedSubOptions)) {
			list($key, $value) = explode(':', $value, 2);
			$this->addMultipleOption($kw . ':' . $key, $value);

			return true;
		}

		return false;
	}


	/**
	 * @return int
	 */
	public function getPage(): int {
		return $this->page;
	}

	/**
	 * @param int $page
	 *
	 * @return ISearchRequest
	 */
	public function setPage(int $page): ISearchRequest {
		if ($page < 1) {
			$page = 1;
		}

		$this->page = $page;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getSize(): int {
		return $this->size;
	}

	/**
	 * @param int $size
	 *
	 * @return ISearchRequest
	 */
	public function setSize(int $size): ISearchRequest {
		$this->size = $size;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * @param array $options
	 *
	 * @return ISearchRequest
	 */
	public function setOptions(array $options): ISearchRequest {
		$this->options = $options;

		return $this;
	}

	/**
	 * @param $option
	 * @param $value
	 *
	 * @return ISearchRequest
	 */
	public function addOption(string $option, string $value): ISearchRequest {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param array $value
	 *
	 * @return ISearchRequest
	 */
	public function addOptionArray(string $option, array $value): ISearchRequest {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param bool $value
	 *
	 * @return ISearchRequest
	 */
	public function addOptionBool(string $option, bool $value): ISearchRequest {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param string $value
	 *
	 * @return ISearchRequest
	 */
	public function addMultipleOption(string $option, string $value): ISearchRequest {
		if (!array_key_exists($option, $this->options)) {
			$this->options[$option] = [];
		}

		$this->options[$option][] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param string $default
	 *
	 * @return string
	 */
	public function getOption(string $option, string $default = ''): string {
		return $this->get($option, $this->options, $default);
	}


	/**
	 * @param string $option
	 * @param array $default
	 *
	 * @return array
	 */
	public function getOptionArray(string $option, array $default = []): array {
		return $this->getArray($option, $this->options, $default);
	}


	/**
	 * @param string $part
	 *
	 * @return ISearchRequest
	 */
	public function addPart(string $part): ISearchRequest {
		$this->parts[] = $part;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getParts(): array {
		return $this->parts;
	}


	/**
	 * @param array $parts
	 *
	 * @return ISearchRequest
	 * @since 15.0.0
	 *
	 */
	public function setParts(array $parts): ISearchRequest {
		$this->parts = $parts;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * @param array $fields
	 *
	 * @return ISearchRequest
	 */
	public function setFields(array $fields): ISearchRequest {
		$this->fields = $fields;

		return $this;
	}


	/**
	 * @param string $field
	 *
	 * @return ISearchRequest
	 */
	public function addLimitField(string $field): ISearchRequest {
		array_push($this->limitFields, $field);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getLimitFields(): array {
		return $this->limitFields;
	}


	/**
	 * @param string $field
	 *
	 * @return ISearchRequest
	 */
	public function addField(string $field): ISearchRequest {
		$this->fields[] = $field;

		return $this;
	}


	/**
	 * @param string $tag
	 *
	 * @return ISearchRequest
	 */
	public function addTag(string $tag): ISearchRequest {
		$this->tags[] = $tag;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getTags(): array {
		return $this->tags;
	}

	/**
	 * @param array $tags
	 *
	 * @return ISearchRequest
	 */
	public function setTags(array $tags): ISearchRequest {
		$this->tags = $tags;

		return $this;
	}


	/**
	 * @param array $tags
	 *
	 * @return ISearchRequest
	 */
	public function setMetaTags(array $tags): ISearchRequest {
		$this->metaTags = $tags;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMetaTags(): array {
		return $this->metaTags;
	}

	/**
	 * @param string $tag
	 *
	 * @return ISearchRequest
	 */
	public function addMetaTag(string $tag): ISearchRequest {
		$this->metaTags[] = $tag;

		return $this;
	}


	/**
	 * @param array $tags
	 *
	 * @return ISearchRequest
	 */
	public function setSubTags(array $tags): ISearchRequest {
		$this->subTags = $tags;

		return $this;
	}

	/**
	 * @param bool $formatted
	 *
	 * @return array
	 */
	public function getSubTags(bool $formatted = false): array {
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
	 * @return ISearchRequest
	 */
	public function addSubTag(string $source, string $tag): ISearchRequest {
		if (!array_key_exists($source, $this->subTags)) {
			$this->subTags[$source] = [];
		}

		$this->subTags[$source][] = $tag;

		return $this;
	}


	/**
	 * @param string $field
	 *
	 * @return ISearchRequest
	 */
	public function addWildcardField(string $field): ISearchRequest {
		$this->wildcardFields[] = $field;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getWildcardFields(): array {
		return $this->wildcardFields;
	}

//
//	/**
//	 * @param array $query
//	 *
//	 * @return ISearchRequest
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
//	 * @return ISearchRequest
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
	 * @return ISearchRequest
	 */
	public function addWildcardFilter(array $filter): ISearchRequest {
		$this->addWildcardFilters([$filter]);

		return $this;
	}

	/**
	 * @param array $filters
	 *
	 * @return ISearchRequest
	 */
	public function addWildcardFilters(array $filters): ISearchRequest {
		array_push($this->wildcardFilters, $filters);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getWildcardFilters(): array {
		return $this->wildcardFilters;
	}


	/**
	 * @param string $filter
	 *
	 * @return ISearchRequest
	 */
	public function addRegexFilter(string $filter): ISearchRequest {
		$this->addRegexFilters([$filter]);

		return $this;
	}

	/**
	 * @param array $filters
	 *
	 * @return ISearchRequest
	 */
	public function addRegexFilters(array $filters): ISearchRequest {
		array_push($this->regexFilters, $filters);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRegexFilters(): array {
		return $this->regexFilters;
	}


	/**
	 * @param ISearchRequestSimpleQuery $query
	 *
	 * @return ISearchRequest
	 */
	public function addSimpleQuery(ISearchRequestSimpleQuery $query): ISearchRequest {
		$this->simpleQueries[] = $query;

		return $this;
	}


	/**
	 * @return ISearchRequestSimpleQuery[]
	 */
	public function getSimpleQueries(): array {
		return $this->simpleQueries;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'providers' => $this->getProviders(),
			'author' => $this->getAuthor(),
			'search' => $this->getSearch(),
			'empty_search' => $this->isEmptySearch(),
			'page' => $this->getPage(),
			'size' => $this->getSize(),
			'parts' => $this->getParts(),
			'queries' => $this->getSimpleQueries(),
			'options' => $this->getOptions(),
			'metatags' => $this->getMetaTags(),
			'subtags' => $this->getSubTags(),
			'tags' => $this->getTags()
		];
	}


	/**
	 * @param array $arr
	 *
	 * @return SearchRequest
	 */
	public function importFromArray($arr): SearchRequest {
		$providers = $arr['providers'];
		if (!is_array($providers)) {
			$providers = [$providers];
		}

		$this->setProviders($providers);
		$this->setAuthor($this->get('author', $arr, ''));
		$this->setSearch($this->get('search', $arr, ''));

		// TODO: remove this in nc19:
		if ($this->get('empty_search', $arr, '') === 'true') {
			$this->setEmptySearch(true);
		} else {
			$this->setEmptySearch($this->getBool('empty_search', $arr, false));
		}
		// END TODO

//		$this->setEmptySearch($this->getBool('empty_search', $arr, false));
		$this->setPage($this->getInt('page', $arr, 0));
		$this->setParts($this->getArray('parts', $arr, []));
		$this->setSize($this->getInt('size', $arr, 10));
		$this->setOptions($this->getArray('options', $arr, []));
		$this->setMetaTags($this->getArray('metatags', $arr, []));
		$this->setSubTags($this->getArray('subtags', $arr, []));
		$this->setTags($this->getArray('tags', $arr, []));

		return $this;
	}


	/**
	 * @param string $json
	 *
	 * @return SearchRequest
	 */
	public static function fromJSON(string $json): SearchRequest {
		$searchRequest = new SearchRequest();
		$searchRequest->importFromArray(json_decode($json, true));

		return $searchRequest;
	}

}
