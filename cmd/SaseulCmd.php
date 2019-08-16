<?php

namespace Cmd;

use Composer\Script\Event;

class SaseulCmd
{
    public function echo(Event $event): void
    {
        echo 'hi\n';
    }
}
