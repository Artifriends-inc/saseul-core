<?php

use PHPUnit\Framework\TestCase;
use Saseul\Util\DateTime;

class DateTimeTest extends TestCase
{
    public function testGetTodayDateTime()
    {
        // Arrange
        // Act
        $today = DateTime::Date();
        // Assert
        $this->assertSame(date('YmdHis'), $today);
        $this->assertIsString($today);
    }
}
