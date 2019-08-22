<?php

namespace Saseul\Common;

use ReflectionClass;
use Saseul\Core\Env;
use Saseul\System\Key;

/**
 * Class AbstractResource.
 *
 * 시스템 환경을 구성하기 위한 API.
 */
abstract class AbstractResource implements ResourceInterface
{
    protected $request;
    protected $thash;
    protected $publicKey;
    protected $signature;

    protected $type;
    protected $from;
    protected $timestamp;

    public function initialize(array $request, string $thash, string $publicKey, string $signature): void
    {
        $this->request = $request;
        $this->thash = $thash;
        $this->publicKey = $publicKey;
        $this->signature = $signature;

        $this->type = $request['type'] ?? '';
        $this->from = $request['from'] ?? '';
        $this->timestamp = $request['timestamp'] ?? 0;
    }

    public function getValidity(): bool
    {
        return $this->isValidityParam()
            && $this->isValidityKey();
    }

    abstract public function process(): void;

    abstract public function getResponse(): array;

    private function isValidityParam(): bool
    {
        $calledRequest = new ReflectionClass(get_class($this));

        return $this->type === $calledRequest->getShortName()
            && $this->from === Env::$nodeInfo['address']
            && $this->publicKey === Env::$nodeInfo['public_key'];
    }

    private function isValidityKey(): bool
    {
        return Key::isValidAddress($this->from, $this->publicKey)
            && Key::isValidSignature($this->thash, $this->publicKey, $this->signature);
    }
}
