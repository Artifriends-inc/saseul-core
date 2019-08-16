<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Custom\Request\GetAuthToken;

class GetAuthTokenTest extends TestCase
{
    public function testSutInheritsAbstractRequest(): void
    {
        // Arrange
        $sut = new GetAuthToken();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
