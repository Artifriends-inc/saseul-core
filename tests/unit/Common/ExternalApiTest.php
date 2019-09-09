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
        $actual = $sut->main();

        // Assert
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::BAD_REQUEST, $actual->getCode());
        $this->assertEmpty($actual->getData());
    }
}
