<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OneSignalPlayerValidator;

class CleanInvalidPlayerIds extends Command
{
    protected $signature = 'onesignal:clean-player-ids';
    protected $description = 'Cleans invalid Player IDs from OneSignal.';

    public function handle()
    {
        OneSignalPlayerValidator::runCleanupCommand();
    }
}
