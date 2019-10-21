<?php

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetBalance;

class GetBalanceTest extends TestCase
{
    public function testSutInheritsAbstractRequest()
    {
        // Arrange
        $sut = new GetBalance();

        // Assert
        static::assertInstanceOf(AbstractRequest::class, $sut);
    }
}
