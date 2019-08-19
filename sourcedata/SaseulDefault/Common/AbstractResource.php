<?php

namespace Saseul\Common;

use ReflectionClass;
use Saseul\Core\Env;
use Saseul\System\Key;

/**
 * Class AbstractResource
 *
 * 시스템 환경을 구성하기 위한 API.
 *
 * @package Saseul\Common
 */
abstract class AbstractResource implements ResourceInterface
{
    protected $request;
    protected $thash;
    protected $public_key;
    protected $signature;

    protected $type;
    protected $from;
    protected $timestamp;

    public function initialize(array $request, string $thash, string $public_key, string $signature): void
    {
        $this->request = $request;
        $this->thash = $thash;
        $this->public_key = $public_key;
        $this->signature = $signature;

        $this->type = $request['type'] ?? '';
        $this->from = $request['from'] ?? '';
        $this->timestamp = $request['timestamp'] ?? 0;
    }

    public function getValidity(): bool
    {
        $calledRequest = new ReflectionClass(get_class($this));

        return $this->type === $calledRequest->getShortName()
            && Env::$nodeInfo['public_key'] === $this->public_key
            && Key::isValidAddress($this->from, $this->public_key)
            && Key::isValidSignature($this->thash, $this->public_key, $this->signature);
    }

    abstract public function process(): void;

    abstract public function getResponse(): array;
}
