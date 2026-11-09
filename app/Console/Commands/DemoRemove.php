<?php

namespace App\Console\Commands;

use App\Services\DemoContent;
use Illuminate\Console\Command;

class DemoRemove extends Command
{
    protected $signature = 'demo:remove';

    protected $description = 'Remove the sample content added by demo:install and restore the previous settings';

    public function handle(DemoContent $demo): int
    {
        if (! $demo->installed()) {
            $this->info('No sample content is installed.');

            return self::SUCCESS;
        }
        $removed = $demo->remove();
        $this->info('Sample content removed. Your own content was not touched.');
        $this->table(['Removed', 'Count'], collect($removed)->map(fn ($n, $c) => [$c, $n])->values()->all());

        return self::SUCCESS;
    }
}
