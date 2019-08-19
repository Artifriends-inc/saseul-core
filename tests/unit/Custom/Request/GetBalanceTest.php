<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Custom\Request\GetBalance;

class GetBalanceTest extends TestCase
{
    public function testSutInheritsAbstractRequest()
    {
        # Arrange
        $sut = new GetBalance();

        # Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}