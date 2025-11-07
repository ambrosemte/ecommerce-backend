<?php

namespace App\Services;

use Ably\AblyRest;

class AblyService
{
    protected $ably;

    public function __construct()
    {
        $this->ably = new AblyRest(env('ABLY_KEY'));
    }

    public function publish($channel, $event, $data)
    {
        $this->ably->channel($channel)->publish($event, $data);
    }
}
