<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Custom\Request\GetAllAuthTokenInfo;

class GetAllAuthTokenInfoTest extends TestCase
{
    public function testSutInheritsAbstractRequest(): void
    {
        // Arrange
        $sut = new GetAllAuthTokenInfo();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
