<?php

namespace Saseul\Test\Unit\Common;

use PHPUnit\Framework\TestCase;
use Saseul\Common\HandlerLoader;

class HandlerLoaderTest extends TestCase
{
    public function testIfHandlerDoesNotExistThenReturnsNotFound(): void
    {
        // Arrange
        $_SERVER['REQUEST_URI'] = $this->generateInvalidHandler();
        $handlerLoader = new HandlerLoader();

        // Act
        $response = $handlerLoader->run();

        // Assert
        $this->assertSame(404, $response->getCode());
    }

    private function generateInvalidHandler($length = 6): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return '/' . $randomString;
    }
}
