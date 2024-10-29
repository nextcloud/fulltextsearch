<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Service;


use OCA\FullTextSearch\Model\Runner;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class CliService
 *
 * @package OCA\FullTextSearch\Service
 */
class CliService {
	/** @var Runner */
	private $runner;

	/** @var array */
	private $panels = [];

	/** @var array */
	private $displayedPanel = [];

	/** @var ProgressBar */
	private $display;

	/** @var OutputInterface */
	private $output = null;

	public function __construct() {
	}


	/**
	 * @param Runner $runner
	 */
	public function setRunner(Runner $runner) {
		$this->runner = $runner;

		$this->runner->onInfoUpdate([$this, 'onInfoUpdated']);
	}


	/**
	 * @param string $panelId
	 * @param array $lines
	 */
	public function createPanel(string $panelId, array $lines) {
		if (!is_array($lines)) {
			$lines = [$lines];
		}

		$this->panels[$panelId] = $lines;
	}


	/**
	 *
	 */
	public function initDisplay() {
		$this->displayedPanel = [];
	}


	/**
	 * @param string $panelSlot
	 * @param string $panelId
	 */
	public function displayPanel(string $panelSlot, string $panelId) {
		$this->displayedPanel[] = [
			'slot' => $panelSlot,
			'id'   => $panelId
		];
	}


	/**
	 * @param string $panelSlot
	 *
	 * @return string
	 */
	public function currentPanel(string $panelSlot): string {
		foreach ($this->displayedPanel as $panel) {
			if ($panel['slot'] === $panelSlot) {
				return $panel['id'];
			}
		}

		return '';
	}


	/**
	 * @param string $panelSlot
	 * @param string $panelId
	 */
	public function switchPanel(string $panelSlot, string $panelId) {
		$this->displayedPanel = array_map(
			function($item) use ($panelId, $panelSlot) {
				if ($item['slot'] === $panelSlot) {
					$item['id'] = $panelId;
				}

				return $item;
			}, $this->displayedPanel
		);

		$this->refreshDisplay();
	}

	/**
	 * @param OutputInterface $output
	 */
	public function runDisplay(OutputInterface $output) {
		$this->output = $output;

		$output->writeLn('');
		foreach ($this->displayedPanel as $displayedPanel) {
			$panel = $this->panels[$displayedPanel['id']];
			for ($i = 0; $i < sizeof($panel); $i++) {
				$output->writeLn('');
			}
		}

		$this->display = new ProgressBar($this->output);
		$this->display->setOverwrite(true);

		$initVar = $this->runner->getInfoAll();
		$keys = array_keys($initVar);
		foreach ($keys as $key) {
			$this->display->setMessage((string)$initVar[$key], (string)$key);
		}

		$this->display->clear();

		$this->refreshDisplay();
	}


	/**
	 *
	 */
	public function refreshDisplay() {

		if ($this->display === null) {
			return;
		}

		$format = [];
		foreach ($this->displayedPanel as $displayedPanel) {
			$panel = $this->panels[$displayedPanel['id']];
			for ($i = 0; $i < sizeof($panel); $i++) {
				$format[] = $panel[$i];
			}
		}

		$this->display->setFormat(implode("\n", $format) . "\n");
		$this->refreshInfo();
		$this->display->start();
	}


	/**
	 *
	 */
	public function refreshInfo() {
		if ($this->runner->isPauseRunning()) {
			$this->display->setMessage('(paused)', '_paused');
		} else {
			$this->display->setMessage('', '_paused');
		}

		$this->display->display();
	}

	/**
	 * @param array $info
	 */
	public function onInfoUpdated(array $info) {
		if ($this->display === null) {
			return;
		}

		$keys = array_keys($info);
		foreach ($keys as $k) {
			$this->display->setMessage((string)$info[$k], (string)$k);
		}
		$this->refreshInfo();

		$this->display->display();
	}

}

