<?php

namespace Saseul\Test\Unit\Models;

use PHPUnit\Framework\TestCase;
use Saseul\DataAccess\TrackerDocument;

class TrackerDocumentTest extends TestCase
{
    public function testTrackerHasHostProperty(): void
    {
        $this->assertClassHasAttribute(
            'host',
            TrackerDocument::class,
            'Tracker model class does not have host property'
        );
    }

    public function testTrackerHasAddressProperty(): void
    {
        $this->assertClassHasAttribute(
            'address',
            TrackerDocument::class,
            'Tracker model class does not have address property'
        );
    }

    public function testTrackerHasRoleProperty(): void
    {
        $this->assertClassHasAttribute(
            'role',
            TrackerDocument::class,
            'Tracker model class does not have role property'
        );
    }

    public function testTrackerHasStatusProperty(): void
    {
        $this->assertClassHasAttribute(
            'status',
            TrackerDocument::class,
            'Tracker model class does not have status property'
        );
    }

    /**
     * @dataProvider generateData
     */
    public function testConstructorSetsProperties(
        string $host,
        string $address,
        string $role,
        string $status
    ): void {
        // Act
        $sut = new TrackerDocument($host, $address, $role, $status);

        // Assert
        $this->assertSame($host, $sut->host);
        $this->assertSame($address, $sut->address);
        $this->assertSame($role, $sut->role);
        $this->assertSame($status, $sut->status);
    }

    public function generateData(): array
    {
        $host = $this->generateValue();
        $address = $this->generateValue();
        $role = $this->generateValue();
        $status = $this->generateValue();

        return [[
            $host,
            $address,
            $role,
            $status
        ]];
    }

    private function generateValue($length = 7): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $generatedString = '';
        for ($i = 0; $i < $length; $i++) {
            $generatedString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $generatedString;
    }
}
