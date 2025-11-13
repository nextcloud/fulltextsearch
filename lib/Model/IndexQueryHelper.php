<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Model;

use NCU\FullTextSearch\IIndexQueryHelper;

class IndexQueryHelper implements IIndexQueryHelper {
	private bool $needed = true;

	public function __construct() {
	}

	public function notNeeded(): void {
		$this->needed = false;
	}

	public function isNeeded(): bool {
		return $this->needed;
	}
}
