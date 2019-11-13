<?php

namespace Saseul\Test\Unit\Custom\Request;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetBalance;

class GetBalanceTest extends TestCase
{
    public function testSutInheritsAbstractRequest(): void
    {
        // Arrange
        $sut = new GetBalance();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
