<?php

namespace Saseul\Api;

use Saseul\Common\ExternalApi;
use Saseul\System\HttpStatus;

class Request extends ExternalApi
{
    public function handle(): void
    {
        $this->initialize();
        if ($this->api->getValidity()) {
            $this->makeResult(
                HttpStatus::OK,
                $this->api->getResponse()
            );

            return;
        }

        $this->makeResult(HttpStatus::BAD_REQUEST);
    }

    private function initialize(): void
    {
        $thash = hash('sha256', json_encode($this->handlerData));
        $this->api->initialize(
            $this->handlerData,
            $thash,
            $this->public_key,
            $this->signature
        );
    }
}
