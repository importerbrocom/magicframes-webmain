<?php

namespace App\Console\Commands;

use App\Services\InstagramFeed;
use Illuminate\Console\Command;

class InstagramSync extends Command
{
    protected $signature = 'instagram:sync';

    protected $description = 'Fetch the latest Instagram posts for the home page';

    public function handle(InstagramFeed $feed): int
    {
        if (! $feed->token()) {
            $this->line('No Instagram token saved - nothing to do.');

            return self::SUCCESS;
        }
        $r = $feed->sync();
        $r['ok'] ? $this->info($r['message']) : $this->error($r['message']);

        return $r['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
