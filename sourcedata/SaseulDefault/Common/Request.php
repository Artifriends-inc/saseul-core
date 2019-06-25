<?php

namespace Saseul\Common;

class Request implements RequestInterface
{
    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
    }

    public function getValidity(): bool
    {
        return false;
    }

    public function getResponse(): array
    {
        return [];
    }
}
