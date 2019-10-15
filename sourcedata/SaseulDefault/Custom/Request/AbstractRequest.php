<?php

namespace Saseul\Custom\Request;

use Saseul\System\Key;
use Saseul\Version;

abstract class AbstractRequest implements RequestInterface
{
    protected $thash;
    protected $public_key;
    protected $signature;
    protected $type;
    protected $version;
    protected $from;
    protected $timestamp;

    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $request['type'] ?? '';
        $this->version = $request['version'] ?? '';
        $this->from = $request['from'] ?? '';
        $this->timestamp = $request['timestamp'] ?? 0;
    }

    public function getValidity(): bool
    {
        $calledRequest = new \ReflectionClass(get_class($this));

        return Version::isValid($this->version)
            && !empty($this->timestamp)
            && $this->type === $calledRequest->getShortName()
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    abstract public function getResponse(): array;
}
