<?php

namespace Saseul\tests\Common\ExternalApi;

use PHPUnit\Framework\TestCase;
use Saseul\Common\ExternalApi;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;

class ExternalApiTest extends TestCase
{
    public function testGivenNonResultThenReturnsBadRequest(): void
    {
        // Arrange
        $sut = new ExternalApi();

        // Act
        $actual = $sut->run();

        // Assert
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::OK, $actual->getCode());
        $this->assertEmpty($actual->getData());
    }
}
