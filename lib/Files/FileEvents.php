<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Files;

use NCU\FullTextSearch\Exceptions\ServiceNotFoundException;
use NCU\FullTextSearch\IManager as IFullTextSearchManager;
use OCA\FullTextSearch\Service\LoggerService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeDeletedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use Psr\Log\LoggerInterface;

readonly class FileEvents implements IEventListener {
	public function __construct(
		private IFullTextSearchManager $fullTextSearchManager,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		$this->logger->warning('handle 1');
		try {
			$service = $this->fullTextSearchManager->getService();
		} catch (ServiceNotFoundException) {
			// no service available
			return;
		}
		$this->logger->warning('handle 2');

		if ($event instanceof NodeCreatedEvent
			|| $event instanceof NodeWrittenEvent
			|| $event instanceof NodeRenamedEvent
			|| $event instanceof NodeDeletedEvent) {
			try {
				$node = $event->getNode();
				$service->requestIndex('files', (string)$node->getId());
			} catch (InvalidPathException|NotFoundException) {
				// cannot reach document
				return;
			}
		}

		if ($event instanceof ShareCreatedEvent
			|| $event instanceof ShareDeletedEvent) {
			try {
				$node = $event->getShare()->getNode();
				$service->requestIndex('files', (string)$node->getId());
			} catch (NotFoundException|InvalidPathException) {
				// cannot reach document
				return;
			}
		}
	}
}
