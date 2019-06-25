<?php

namespace Saseul\Common;

class Resource implements ResourceInterface
{
    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
    }

    public function process(): void
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
