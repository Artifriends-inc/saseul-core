<?php

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetTransaction;

class GetTransactionTest extends TestCase
{
    public function testSutInheritsAbstractRequest()
    {
        // Arrange
        $sut = new GetTransaction();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
