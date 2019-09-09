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
            $this->assembleResult(
                HttpStatus::OK,
                $this->api->getResponse()
            );

            return;
        }

        $this->assembleResult(HttpStatus::BAD_REQUEST);
    }

    private function initialize(): void
    {
        $thash = hash('sha256', json_encode($this->request));
        $this->api->initialize(
            $this->request,
            $thash,
            $this->public_key,
            $this->signature
        );
    }
}
