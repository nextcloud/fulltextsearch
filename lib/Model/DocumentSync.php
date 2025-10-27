<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FullTextSearch\Model;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setProviderId(string $providerId)
 * @method string getProviderId()
 * @method void setDocumentId(string $documentId)
 * @method string getDocumentId()
 * @method void setFlags(int $indexed)
 * @method int getFlags()
 * @method void setIndexed(int $indexed)
 * @method int getIndexed()
 * @method void setChecksum(string $checksum)
 * @method string getChecksum()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentSync extends Entity {
	protected string $providerId = '';
	protected string $documentId = '';
	protected int $flags = 0;
	protected int $indexed = 0;
	protected string $checksum = '';

	public function __construct(
	) {
		$this->addType('providerId', 'string');
		$this->addType('documentId', 'string');
		$this->addType('flags', 'integer');
		$this->addType('indexed', 'integer');
		$this->addType('checksum', 'string');
	}

	public function definition(): string {
		return $this->getProviderId() . '/' . $this->getDocumentId();
	}
}
