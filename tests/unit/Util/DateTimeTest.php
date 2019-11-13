<?php

namespace Saseul\Test\Unit\Util;

use PHPUnit\Framework\TestCase;
use Saseul\Util\DateTime;

class DateTimeTest extends TestCase
{
    public function testGetTodayDateTime(): void
    {
        // Arrange
        // Act
        $today = DateTime::Date();
        // Assert
        $this->assertSame(date('YmdHis'), $today);
        $this->assertIsString($today);
    }
}
