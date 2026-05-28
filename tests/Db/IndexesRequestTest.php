<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Tests\Db;

use OCA\FullTextSearch\Db\IndexesRequest;
use OCA\FullTextSearch\Exceptions\IndexDoesNotExistException;
use OCA\FullTextSearch\Model\Index;
use OCP\FullTextSearch\Model\IIndex;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
class IndexesRequestTest extends TestCase {
	private IndexesRequest $indexesRequest;
	private string $testCollection = 'test-collection';

	protected function setUp(): void {
		parent::setUp();
		$this->indexesRequest = Server::get(IndexesRequest::class);
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->indexesRequest->deleteCollection($this->testCollection);
	}

	private function makeIndex(string $providerId = 'test.provider', string $documentId = 'doc1'): Index {
		$index = new Index($providerId, $documentId, $this->testCollection);
		$index->setOwnerId('user1')
			  ->setSource('files')
			  ->setStatus(IIndex::INDEX_FULL)
			  ->setLastIndex(1000);
		return $index;
	}

	public function testCreateAndGetIndex(): void {
		$index = $this->makeIndex();
		$this->indexesRequest->create($index);

		$fetched = $this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);

		$this->assertSame('test.provider', $fetched->getProviderId());
		$this->assertSame('doc1', $fetched->getDocumentId());
		$this->assertSame($this->testCollection, $fetched->getCollection());
		$this->assertSame('user1', $fetched->getOwnerId());
		$this->assertSame('files', $fetched->getSource());
		$this->assertSame(IIndex::INDEX_FULL, $fetched->getStatus());
		$this->assertSame(1000, $fetched->getLastIndex());
	}

	public function testGetIndexThrowsWhenNotFound(): void {
		$this->expectException(IndexDoesNotExistException::class);
		$this->indexesRequest->getIndex('nonexistent.provider', 'missing-doc', $this->testCollection);
	}

	public function testGetIndexes(): void {
		$this->indexesRequest->create($this->makeIndex('test.provider', 'doc1'));
		$this->indexesRequest->create($this->makeIndex('test.provider', 'doc2'));

		$indexes = $this->indexesRequest->getIndexes('test.provider', 'doc1');

		$this->assertCount(1, $indexes);
		$this->assertSame('doc1', $indexes[0]->getDocumentId());
	}

	public function testUpdateIndex(): void {
		$index = $this->makeIndex();
		$this->indexesRequest->create($index);

		$index->setStatus(IIndex::INDEX_OK, true)
			  ->setSource('photos')
			  ->setLastIndex(2000);
		$this->indexesRequest->update($index);

		$fetched = $this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);
		$this->assertSame(IIndex::INDEX_OK, $fetched->getStatus());
		$this->assertSame('photos', $fetched->getSource());
		$this->assertSame(2000, $fetched->getLastIndex());
	}

	public function testUpdateStatusOnly(): void {
		$index = $this->makeIndex();
		$index->setSource('files');
		$this->indexesRequest->create($index);

		$index->setStatus(IIndex::INDEX_OK, true);
		$this->indexesRequest->update($index, statusOnly: true);

		$fetched = $this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);
		$this->assertSame(IIndex::INDEX_OK, $fetched->getStatus());
		// source must be unchanged when statusOnly is true
		$this->assertSame('files', $fetched->getSource());
	}

	public function testGetQueuedIndexes(): void {
		$index1 = $this->makeIndex('test.provider', 'queued1');
		$index1->setStatus(IIndex::INDEX_FULL, true);
		$this->indexesRequest->create($index1);

		$index2 = $this->makeIndex('test.provider', 'ok1');
		$index2->setStatus(IIndex::INDEX_OK, true);
		$this->indexesRequest->create($index2);

		$queued = $this->indexesRequest->getQueuedIndexes($this->testCollection);
		$docIds = array_map(fn (Index $i) => $i->getDocumentId(), $queued);

		$this->assertContains('queued1', $docIds);
		$this->assertNotContains('ok1', $docIds);
	}

	public function testGetQueuedIndexesWithLength(): void {
		for ($i = 0; $i < 5; $i++) {
			$index = $this->makeIndex('test.provider', "doc$i");
			$index->setStatus(IIndex::INDEX_FULL, true);
			$this->indexesRequest->create($index);
		}

		$queued = $this->indexesRequest->getQueuedIndexes($this->testCollection, length: 3);
		$this->assertCount(3, $queued);
	}

	public function testAddAndResetError(): void {
		$index = $this->makeIndex();
		$index->addError('Something went wrong', 'RuntimeException', IIndex::ERROR_SEV_3);
		$this->indexesRequest->create($index);

		$errors = $this->indexesRequest->getErrorIndexes();
		$errorDocIds = array_map(fn (Index $i) => $i->getDocumentId(), $errors);
		$this->assertContains('doc1', $errorDocIds);

		$this->indexesRequest->resetError($index);
		$fetched = $this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);
		$this->assertSame(0, $fetched->getErrorCount());
		$this->assertSame([], $fetched->getErrors());
	}

	public function testResetErrorReturnsFalseForMissingIndex(): void {
		$index = new Index('nonexistent', 'none', $this->testCollection);
		$result = $this->indexesRequest->resetError($index);
		$this->assertFalse($result);
	}

	public function testResetAllErrors(): void {
		$index1 = $this->makeIndex('test.provider', 'e1');
		$index1->addError('err1');
		$index2 = $this->makeIndex('test.provider', 'e2');
		$index2->addError('err2');
		$this->indexesRequest->create($index1);
		$this->indexesRequest->create($index2);

		$this->indexesRequest->resetAllErrors();

		foreach (['e1', 'e2'] as $docId) {
			$fetched = $this->indexesRequest->getIndex('test.provider', $docId, $this->testCollection);
			$this->assertSame(0, $fetched->getErrorCount());
		}
	}

	public function testDeleteIndex(): void {
		$index = $this->makeIndex();
		$this->indexesRequest->create($index);

		$this->indexesRequest->deleteIndex($index);

		$this->expectException(IndexDoesNotExistException::class);
		$this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);
	}

	public function testDeleteFromProviderId(): void {
		$this->indexesRequest->create($this->makeIndex('provider.a', 'doc1'));
		$this->indexesRequest->create($this->makeIndex('provider.a', 'doc2'));
		$this->indexesRequest->create($this->makeIndex('provider.b', 'doc3'));

		$this->indexesRequest->deleteFromProviderId('provider.a');

		$remaining = $this->indexesRequest->getIndexes('provider.b', 'doc3');
		$this->assertCount(1, $remaining);

		$this->expectException(IndexDoesNotExistException::class);
		$this->indexesRequest->getIndex('provider.a', 'doc1', $this->testCollection);
	}

	public function testReset(): void {
		$this->indexesRequest->create($this->makeIndex('test.provider', 'doc1'));
		$this->indexesRequest->create($this->makeIndex('test.provider', 'doc2'));

		$this->indexesRequest->reset($this->testCollection);

		$this->expectException(IndexDoesNotExistException::class);
		$this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);
	}

	public function testOptionsArePersistedCorrectly(): void {
		$index = $this->makeIndex();
		$index->addOption('color', 'blue');
		$index->addOptionInt('count', 42);
		$this->indexesRequest->create($index);

		$fetched = $this->indexesRequest->getIndex('test.provider', 'doc1', $this->testCollection);
		$this->assertSame('blue', $fetched->getOption('color'));
		$this->assertSame(42, $fetched->getOptionInt('count'));
	}
}
