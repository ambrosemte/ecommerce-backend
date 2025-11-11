<?php

namespace App\Services;

use Ably\AblyRest;
use Ably\Exceptions\AblyException;
use Exception;
use Illuminate\Support\Facades\Log;

class AblyService
{
    protected $ably;

    public function __construct()
    {
        $this->ably = new AblyRest(env('ABLY_KEY'));
    }

    public function publish($channel, $event, $data)
    {
        try {
            $this->ably->channel($channel)->publish($event, $data);
        } catch (AblyException | Exception $e) {
            Log::error('Ably error: ' . $e->getMessage());
        }

    }
}
