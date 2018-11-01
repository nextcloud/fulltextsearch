<?php
declare(strict_types=1);


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


use daita\MySmallPhpTools\Traits\TArrayTools;
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

