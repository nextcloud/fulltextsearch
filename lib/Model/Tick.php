<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;


use OCA\FullTextSearch\Tools\Traits\TArrayTools;

/**
 * Class Tick
 *
 * @package OCA\FullTextSearch\Model
 */
class Tick {


	use TArrayTools;


	/** @var int */
	private $id;

	/** @var string */
	private $source;

	/** @var array */
	protected $data;

	/** @var int */
	private $tick;

	/** @var int */
	private $firstTick;

	/** @var string */
	private $status;

	/** @var string */
	private $action = '';


	/**
	 * Tick constructor.
	 *
	 * @param string $source
	 * @param int $id
	 */
	public function __construct(string $source, int $id = 0) {
		$this->source = $source;
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return $this
	 */
	public function setId(int $id): Tick {
		$this->id = $id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSource(): string {
		return $this->source;
	}


	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setData(array $data): Tick {
		$this->data = $data;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getTick(): int {
		return $this->tick;
	}

	/**
	 * @param int $tick
	 *
	 * @return $this
	 */
	public function setTick(int $tick = 0): Tick {
		if ($tick === 0) {
			$tick = time();
		}

		$this->tick = $tick;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getFirstTick(): int {
		return $this->firstTick;
	}

	/**
	 * @param int $tick
	 *
	 * @return $this
	 */
	public function setFirstTick(int $tick = 0): Tick {
		if ($tick === 0) {
			$tick = time();
		}

		$this->firstTick = $tick;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return $this
	 */
	public function setStatus(string $status): Tick {
		$this->status = $status;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getAction(): string {
		return $this->action;
	}

	/**
	 * @param string $action
	 *
	 * @return $this
	 */
	public function setAction(string $action): Tick {
		$this->action = $action;

		return $this;
	}


	/**
	 * @param string $info
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setInfo(string $info, string $value): Tick {
		$this->data[$info] = $value;

		return $this;
	}

	/**
	 * @param string $info
	 * @param int $value
	 *
	 * @return $this
	 */
	public function setInfoInt(string $info, int $value): Tick {
		$this->data[$info] = $value;

		return $this;
	}

	/**
	 * @param string $info
	 * @param float $value
	 *
	 * @return $this
	 */
	public function setInfoFloat(string $info, float $value): Tick {
		$this->data[$info] = $value;

		return $this;
	}

	/**
	 * @param string $info
	 */
	public function unsetInfo(string $info) {
		unset($this->data[$info]);
	}

	/**
	 * @param string $info
	 * @param string $default
	 *
	 * @return string
	 */
	public function getInfo(string $info, string $default = ''): string {
		return $this->get($info, $this->data, $default);
	}


	/**
	 * @param string $info
	 * @param int $default
	 *
	 * @return int
	 */
	public function getInfoInt(string $info, int $default = 0): int {
		return $this->getInt($info, $this->data, $default);
	}

	/**
	 * @param string $info
	 * @param float $default
	 *
	 * @return float
	 */
	public function getInfoFloat(string $info, float $default = 0): float {
		return $this->getFloat($info, $this->data, $default);
	}

}

