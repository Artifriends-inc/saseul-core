<?php

namespace Saseul\Common;

use Saseul\System\HttpResponse;
use Saseul\System\HttpStatus;

class ExternalApi
{
    protected $result;

    public function run(): HttpResponse
    {
        $this->result = $this->handle();

        return new HttpResponse(HttpStatus::OK);
    }

    public function handle(): array
    {
        return [];
    }
}
