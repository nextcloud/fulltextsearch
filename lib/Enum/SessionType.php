<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Enum;

enum SessionType: string {
	case UNKNOWN = '';
	case FORCED = 'forced';
	case SYNC = 'sync';
	case RESYNC = 'sync_recent';
}
