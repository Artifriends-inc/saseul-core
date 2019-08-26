<?php

namespace Saseul\Custom\Resource;

use Saseul\Common\AbstractResource;
use Saseul\Core\Tracker;

class InitNode extends AbstractResource
{
    private $nodeRole;

    public function process(): void
    {
        $this->nodeRole = Tracker::addTrackerOnDb();
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array
     */
    public function getResponse(): array
    {
        return [
            'status' => 'success',
            'role' => $this->nodeRole
        ];
    }
}
