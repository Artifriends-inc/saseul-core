<?php

use PHPUnit\Framework\TestCase;
use Saseul\Common\AbstractRequest;
use Saseul\Custom\Request\CheckAuthToken;

class CheckAuthTokenTest extends TestCase
{
    public function testSutInheritsAbstractRequest(): void
    {
        // Arrange
        $sut = new CheckAuthToken();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
