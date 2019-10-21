<?php

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetRole;

class GetRoleTest extends TestCase
{
    public function testSutInheritsAbstractRequest()
    {
        // Arrange
        $sut = new GetRole();

        // Assert
        static::assertInstanceOf(AbstractRequest::class, $sut);
    }
}
