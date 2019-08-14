<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Custom\Request\GetTokenBalance;

class GetTokenBalanceTest extends TestCase
{
    public function testSutInheritsAbstractRequest()
    {
        # Arrange
        $sut = new GetTokenBalance();

        # Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
