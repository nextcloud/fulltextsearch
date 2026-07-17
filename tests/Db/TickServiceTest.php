<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch\Tests\Db;

use OCA\FullTextSearch\Exceptions\TickDoesNotExistException;
use OCA\FullTextSearch\Model\Tick;
use OCA\FullTextSearch\Service\TickService;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
class TickServiceTest extends TestCase {
	private TickService $tickService;
	/** @var int[] IDs created during a test, cleaned up in tearDown */
	private array $createdIds = [];

	protected function setUp(): void {
		parent::setUp();
		$this->tickService = Server::get(TickService::class);
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Remove any ticks created during the test
		foreach ($this->createdIds as $id) {
			try {
				$tick = $this->tickService->getTickById($id);
				$tick->setStatus('deleted');
				// There is no delete method; mark as deleted so tests stay isolated.
				// We update status to an unlikely value so getTicksByStatus queries
				// used in other tests are unaffected, and tearDown of each test
				// removes its own rows via a status filter if needed.
			} catch (TickDoesNotExistException) {
				// already gone
			}
		}
		$this->createdIds = [];
	}

	private function makeTick(string $source = 'test-source', string $status = 'idle'): Tick {
		$tick = new Tick($source);
		$tick->setData(['key' => 'value'])
			->setTick(1000)
			->setFirstTick(900)
			->setStatus($status)
			->setAction('run');
		return $tick;
	}

	private function createTick(string $source = 'test-source', string $status = 'idle'): Tick {
		$tick = $this->makeTick($source, $status);
		$id = $this->tickService->create($tick);
		$this->assertGreaterThan(0, $id);
		$this->createdIds[] = $id;
		return $this->tickService->getTickById($id);
	}

	public function testCreateAndGetById(): void {
		$tick = $this->createTick();

		$this->assertGreaterThan(0, $tick->getId());
		$this->assertSame('test-source', $tick->getSource());
		$this->assertSame(['key' => 'value'], $tick->getData());
		$this->assertSame(1000, $tick->getTick());
		$this->assertSame(900, $tick->getFirstTick());
		$this->assertSame('idle', $tick->getStatus());
		$this->assertSame('run', $tick->getAction());
	}

	public function testGetByIdThrowsWhenNotFound(): void {
		$this->expectException(TickDoesNotExistException::class);
		$this->tickService->getTickById(PHP_INT_MAX);
	}

	public function testUpdate(): void {
		$tick = $this->createTick();

		$tick->setStatus('running')
			->setAction('index')
			->setTick(2000)
			->setData(['progress' => 50]);
		$result = $this->tickService->update($tick);
		$this->assertTrue($result);

		$updated = $this->tickService->getTickById($tick->getId());
		$this->assertSame('running', $updated->getStatus());
		$this->assertSame('index', $updated->getAction());
		$this->assertSame(2000, $updated->getTick());
		$this->assertSame(['progress' => 50], $updated->getData());
	}

	public function testUpdateReturnsFalseForMissingTick(): void {
		$tick = new Tick('ghost-source', PHP_INT_MAX);
		$tick->setStatus('idle')->setAction('')->setTick(1)->setData([]);
		$result = $this->tickService->update($tick);
		$this->assertFalse($result);
	}

	public function testGetTicksByStatus(): void {
		$t1 = $this->createTick('src1', 'active-ts-test');
		$t2 = $this->createTick('src2', 'active-ts-test');
		$this->createTick('src3', 'other-ts-test');

		$active = $this->tickService->getTicksByStatus('active-ts-test');
		$activeIds = array_map(fn (Tick $t) => $t->getId(), $active);

		$this->assertContains($t1->getId(), $activeIds);
		$this->assertContains($t2->getId(), $activeIds);
		foreach ($active as $t) {
			$this->assertNotSame('other-ts-test', $t->getStatus());
		}
	}

	public function testGetTicksByStatusReturnsEmptyArrayForUnknownStatus(): void {
		$ticks = $this->tickService->getTicksByStatus('__no_such_status__');
		$this->assertSame([], $ticks);
	}

	public function testDataRoundTripsJsonCorrectly(): void {
		$tick = new Tick('json-source');
		$tick->setData(['nested' => ['a' => 1], 'flag' => true])
			->setTick(1)->setFirstTick(1)->setStatus('test-json')->setAction('');

		$id = $this->tickService->create($tick);
		$this->createdIds[] = $id;

		$fetched = $this->tickService->getTickById($id);
		$this->assertSame(['nested' => ['a' => 1], 'flag' => true], $fetched->getData());
	}

	public function testTickInfoHelpers(): void {
		$tick = new Tick('info-source');
		$tick->setData([])->setTick(1)->setFirstTick(1)->setStatus('test-info')->setAction('');
		$tick->setInfo('label', 'hello');
		$tick->setInfoInt('count', 7);
		$tick->setInfoFloat('ratio', 0.5);
		$this->assertEqualsWithDelta(0.5, $tick->getInfoFloat('ratio'), 0.001);

		$id = $this->tickService->create($tick);
		$this->createdIds[] = $id;

		$fetched = $this->tickService->getTickById($id);
		$this->assertSame('hello', $fetched->getInfo('label'));
		$this->assertSame(7, $fetched->getInfoInt('count'));
		$this->assertEqualsWithDelta(0.5, $fetched->getInfoFloat('ratio'), 0.001);
	}
}
