<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Service\PlatformService;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

#[AsCommand(
	name: 'fulltextsearch:document:platform',
	description: 'Get document from index',
)]
class DocumentPlatform {
	public function __construct(
		private PlatformService $platformService,
	) {
	}


	/**
	 * @throws Exception
	 */
	public function __invoke(
		IOutput $output,
		#[Argument(description: 'providerId')]
		string $providerId,
		#[Argument(description: 'documentId')]
		string $documentId,
		#[Option(description: 'return some content', shortcut: 'c')]
		bool $content = false,
	): ExitCode {
		$wrapper = $this->platformService->getPlatform();
		$platform = $wrapper->getPlatform();

		$indexDocument = $platform->getDocument($providerId, $documentId);
		$result = [
			'document' => $indexDocument
		];
		if ($content === true) {
			$result['content'] = substr($indexDocument->getContent(), 0, 200);
		}

		$output->writeln(json_encode($result, JSON_PRETTY_PRINT));

		return ExitCode::Success;
	}


}
