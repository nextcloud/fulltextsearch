<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch;


use OC\Core\Command\Base;


/**
 * Abstract class ICommandBase
 */
abstract class ACommandBase extends Base {

	abstract public function abort();

}

