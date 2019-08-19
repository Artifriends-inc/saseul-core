<?php

namespace Saseul\Common;

interface ResourceInterface
{
    public function initialize(
        array $request,
        string $thash,
        string $public_key,
        string $signature
    ): void;

    public function process(): void;

    public function getValidity(): bool;

    public function getResponse(): array;
}
