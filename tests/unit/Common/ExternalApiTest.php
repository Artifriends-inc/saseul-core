<?php

namespace Saseul\tests\Common\ExternalApi;

use PHPUnit\Framework\TestCase;
use Saseul\Common\ExternalApi;
use Saseul\System\HttpRequest;
use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;

class ExternalApiTest extends TestCase
{
    protected function setUp(): void
    {
        $_REQUEST = [];
    }

    public function testGivenNonResultThenReturnsBadRequest(): void
    {
        // Arrange
        $sut = new ExternalApi();
        $request = new HttpRequest($_REQUEST, $_SERVER, $_GET, $_POST);

        // Act
        $actual = $sut->invoke($request);

        // Assert
        $this->assertInstanceOf(HttpResponse::class, $actual);
        $this->assertEquals(HttpStatus::BAD_REQUEST, $actual->getCode());
        $this->assertEmpty($actual->getData());
    }
}
