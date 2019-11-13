<?php

namespace Saseul\Test\Unit\Custom\Request;

use PHPUnit\Framework\TestCase;
use Saseul\Custom\Request\AbstractRequest;
use Saseul\Custom\Request\GetRole;

class GetRoleTest extends TestCase
{
    public function testSutInheritsAbstractRequest(): void
    {
        // Arrange
        $sut = new GetRole();

        // Assert
        $this->assertInstanceOf(AbstractRequest::class, $sut);
    }
}
