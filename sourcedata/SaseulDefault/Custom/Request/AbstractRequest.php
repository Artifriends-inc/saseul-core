<?php

namespace Saseul\Custom\Request;

use ReflectionClass;
use Saseul\System\Key;
use Saseul\Version;

abstract class AbstractRequest implements RequestInterface
{
    /** @var string Transaction hash */
    protected $thash;

    /** @var string Public key */
    protected $public_key;

    /** @var string Request signature */
    protected $signature;

    /** @var string Request Type */
    protected $type;

    /** @var string SASEUL core version */
    protected $version;

    /** @var string Request sender */
    protected $from;

    /** @var int|string Request sending timestamp */
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
        $calledRequest = new ReflectionClass(get_class($this));

        return Version::isValid($this->version)
            && !empty($this->timestamp)
            && $this->type === $calledRequest->getShortName()
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    abstract public function getResponse(): array;
}
