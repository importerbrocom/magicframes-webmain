<?php

namespace App\Console\Commands;

use App\Services\DemoContent;
use Illuminate\Console\Command;

class DemoInstall extends Command
{
    protected $signature = 'demo:install {--force : Replace sample content that is already installed}';

    protected $description = 'Fill the website with clearly-labelled SAMPLE photos, videos, slides, services and posts for previewing';

    public function handle(DemoContent $demo): int
    {
        if ($demo->installed()) {
            if (! $this->option('force')) {
                $this->warn('Sample content is already installed. Use --force to reinstall, or run: php artisan demo:remove');

                return self::FAILURE;
            }
            $demo->remove();
        }

        $this->info('Installing sample content…');
        $counts = $demo->install(fn ($line) => $this->line(' • '.$line));

        $this->newLine();
        $this->table(['Created', 'Count'], collect($counts)->map(fn ($n, $c) => [class_basename($c), $n])->values()->all());
        $this->warn('This is SAMPLE content (fake contact details, sample testimonials/statistics). Remove it before going live: php artisan demo:remove');

        return self::SUCCESS;
    }
}
