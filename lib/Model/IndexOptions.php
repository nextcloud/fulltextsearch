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


use JsonSerializable;
use OCP\FullTextSearch\Model\IIndexOptions;

class IndexOptions implements IIndexOptions, JsonSerializable {

	/**
	 * @var array
	 */
	private $options = [];


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
		if (array_key_exists($option, $this->options)) {
			return $this->options[$option];
		}

		return $default;
	}


	/**
	 * @param string $option
	 * @param array $default
	 *
	 * @return array
	 */
	public function getOptionArray(string $option, array $default = []): array {
		if (array_key_exists($option, $this->options)) {
			$options = $this->options[$option];
			if (is_array($options)) {
				return $this->options[$option];
			}
		}

		return $default;
	}


	/**
	 * @param string $option
	 * @param bool $default
	 *
	 * @return bool
	 */
	public function getOptionBool(string $option, bool $default): bool {
		if (array_key_exists($option, $this->options)) {
			$options = $this->options[$option];
			if (is_bool($options)) {
				return $this->options[$option];
			}
		}

		return $default;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->options;
	}
}
