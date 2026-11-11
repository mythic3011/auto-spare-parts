<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SparePart;
use Exception;

class SparePartTest extends TestCase
{
    private SparePart $sparePart;
    private int $testPartNum = 9999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sparePart = new SparePart();
        // Clean up any leftover test parts
        $this->cleanupTestPart();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestPart();
        parent::tearDown();
    }

    private function cleanupTestPart(): void
    {
        try {
            $this->sparePart->deleteSparePart($this->testPartNum);
        } catch (\Exception $e) {
            // Ignore if not found
        }
    }

    #[Test]
    public function it_retrieves_existing_spare_part(): void
    {
        $part = $this->sparePart->getSparePart('1');

        $this->assertNotNull($part);
        $this->assertArrayHasKey('sparePartNum', $part);
        $this->assertArrayHasKey('sparePartName', $part);
        $this->assertArrayHasKey('price', $part);
        $this->assertArrayHasKey('stockItemQty', $part);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_part(): void
    {
        $part = $this->sparePart->getSparePart('99999');

        $this->assertNull($part);
    }

    #[Test]
    public function it_retrieves_all_spare_parts(): void
    {
        $parts = $this->sparePart->getSparePartList();

        $this->assertIsArray($parts);
        $this->assertGreaterThan(0, count($parts));
    }

    #[Test]
    public function it_adds_new_spare_part(): void
    {
        $data = [
            'sparePartNum'         => $this->testPartNum,
            'sparePartCategory'    => 1,
            'sparePartName'        => 'Test Part',
            'weight'               => 5.0,
            'stockItemQty'         => 10,
            'price'                => 999.99,
            'sparePartDescription' => 'A test spare part',
        ];

        $result = $this->sparePart->addSparePart($data);

        $this->assertNotNull($result);
        $this->assertEquals($this->testPartNum, $result['sparePartNum']);
        $this->assertEquals('Test Part', $result['sparePartName']);
    }

    #[Test]
    public function it_throws_exception_when_adding_part_without_number(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Valid spare part number is required');

        $this->sparePart->addSparePart([
            'sparePartCategory' => 1,
            'sparePartName'     => 'No Number Part',
            'weight'            => 1.0,
            'stockItemQty'      => 5,
            'price'             => 100.0,
        ]);
    }

    #[Test]
    public function it_updates_spare_part(): void
    {
        $this->sparePart->addSparePart([
            'sparePartNum'      => $this->testPartNum,
            'sparePartCategory' => 1,
            'sparePartName'     => 'Test Part',
            'weight'            => 5.0,
            'stockItemQty'      => 10,
            'price'             => 999.99,
        ]);

        $this->sparePart->updateSparePart((string)$this->testPartNum, [
            'price'        => 1299.99,
            'stockItemQty' => 20,
        ]);

        $updated = $this->sparePart->getSparePart((string)$this->testPartNum);

        $this->assertEquals(1299.99, (float)$updated['price']);
        $this->assertEquals(20, (int)$updated['stockItemQty']);
    }

    #[Test]
    public function it_deletes_spare_part(): void
    {
        $this->sparePart->addSparePart([
            'sparePartNum'      => $this->testPartNum,
            'sparePartCategory' => 1,
            'sparePartName'     => 'To Delete',
            'weight'            => 1.0,
            'stockItemQty'      => 1,
            'price'             => 50.0,
        ]);

        $this->assertNotNull($this->sparePart->getSparePart((string)$this->testPartNum));

        $this->sparePart->deleteSparePart((string)$this->testPartNum);

        $this->assertNull($this->sparePart->getSparePart((string)$this->testPartNum));
    }

    #[Test]
    public function it_checks_if_spare_part_exists(): void
    {
        $this->assertTrue($this->sparePart->checkSparePartExists('1'));
        $this->assertFalse($this->sparePart->checkSparePartExists('99999'));
    }

    #[Test]
    public function it_generates_new_spare_part_id(): void
    {
        $newId = $this->sparePart->getNewSparePartId();

        $this->assertIsInt($newId);
        $this->assertGreaterThan(0, $newId);
    }

    #[Test]
    public function it_retrieves_spare_part_by_id(): void
    {
        $part = $this->sparePart->getSparePartById('1');

        $this->assertNotNull($part);
        $this->assertArrayHasKey('sparePartNum', $part);
        $this->assertArrayHasKey('sparePartName', $part);
        $this->assertArrayHasKey('price', $part);
    }

    #[Test]
    public function it_gets_all_spare_parts(): void
    {
        $parts = $this->sparePart->getAllSpareParts();

        $this->assertIsArray($parts);
        $this->assertGreaterThanOrEqual(11, count($parts)); // At least the seeded 11 parts
    }

    #[Test]
    public function it_only_updates_allowed_fields(): void
    {
        $this->sparePart->addSparePart([
            'sparePartNum'      => $this->testPartNum,
            'sparePartCategory' => 1,
            'sparePartName'     => 'Original Name',
            'weight'            => 5.0,
            'stockItemQty'      => 10,
            'price'             => 100.0,
        ]);

        // Try to update a non-allowed field (sparePartName)
        $this->sparePart->updateSparePart((string)$this->testPartNum, [
            'sparePartName' => 'Changed Name', // Not in allowedFields
            'price'         => 200.0,          // Allowed
        ]);

        $updated = $this->sparePart->getSparePart((string)$this->testPartNum);

        // Name should NOT have changed
        $this->assertEquals('Original Name', $updated['sparePartName']);
        // Price SHOULD have changed
        $this->assertEquals(200.0, (float)$updated['price']);
    }
}
