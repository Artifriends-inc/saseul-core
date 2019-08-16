<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Custom\Request\GetAccountInfo;

class GetAccountInfoTest extends TestCase
{
    public function testSutInheritsAbstractRequest(): void
    {
        // Arrange
        $sut = new GetAccountInfo();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
