<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCA\FullTextSearch\Tools\Traits\TArrayTools;
use JsonSerializable;
use OCP\FullTextSearch\Model\IIndexOptions;


/**
 * Class IndexOptions
 *
 * @package OCA\FullTextSearch\Model
 */
class IndexOptions implements IIndexOptions, JsonSerializable {


	use TArrayTools;


	/**
	 * @var array
	 */
	private $options = [];


	/**
	 * IndexOptions constructor.
	 *
	 * @param array $options
	 */
	public function __construct($options = []) {
		$this->options = $options;
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
	 * @return IIndexOptions
	 */
	public function setOptions(array $options): IIndexOptions {
		$this->options = $options;

		return $this;
	}

	/**
	 * @param string $option
	 * @param string $value
	 *
	 * @return IIndexOptions
	 */
	public function addOption(string $option, string $value): IIndexOptions {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param array $value
	 *
	 * @return IIndexOptions
	 */
	public function addOptionArray(string $option, array $value): IIndexOptions {
		$this->options[$option] = $value;

		return $this;
	}

	/**
	 * @param string $option
	 * @param bool $value
	 *
	 * @return IIndexOptions
	 */
	public function addOptionBool(string $option, bool $value): IIndexOptions {
		$this->options[$option] = $value;

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
	 * @param string $option
	 * @param bool $default
	 *
	 * @return bool
	 */
	public function getOptionBool(string $option, bool $default): bool {
		return $this->getBool($option, $this->options, $default);
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->options;
	}
}

