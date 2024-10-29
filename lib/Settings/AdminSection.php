<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Settings;


use OCA\FullTextSearch\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;


/**
 * Class AdminSection
 *
 * @package OCA\FullTextSearch\Settings
 */
class AdminSection implements IIconSection {


	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;


	/**
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IL10N $l10n, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return $this->l10n->t('Full text search');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority(): int {
		return 55;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'fulltextsearch_black.svg');
	}
}
