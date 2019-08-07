<?php


use Saseul\Util\DateTime;
use PHPUnit\Framework\TestCase;

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
