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


	/** @var MiscService */
	private $miscService;

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


	/**
	 * CliService constructor.
	 *
	 * @param MiscService $miscService
	 */
	public function __construct(MiscService $miscService) {
		$this->miscService = $miscService;
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
			$this->display->setMessage($initVar[$key], $key);
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
			$this->display->setMessage((string)$info[$k], $k);
		}
		$this->refreshInfo();

		$this->display->display();
	}

}

