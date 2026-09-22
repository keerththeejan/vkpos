<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearPerformanceCache extends Command
{
    protected $signature = 'vkpos:clear-cache {--opcache : Also reset PHP OPcache if available}';

    protected $description = 'Clear Laravel application/view/cache without touching uploads or database data';

    public function handle()
    {
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');
        $this->line(trim(Artisan::output()));

        $this->info('Clearing compiled views...');
        Artisan::call('view:clear');
        $this->line(trim(Artisan::output()));

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->info('Clearing config cache...');
            Artisan::call('config:clear');
            $this->line(trim(Artisan::output()));
        }

        if (file_exists(base_path('bootstrap/cache/routes-v7.php')) || file_exists(base_path('bootstrap/cache/routes.php'))) {
            $this->info('Clearing route cache...');
            Artisan::call('route:clear');
            $this->line(trim(Artisan::output()));
        }

        if ($this->option('opcache') && function_exists('opcache_reset')) {
            opcache_reset();
            $this->info('PHP OPcache reset.');
        } elseif ($this->option('opcache')) {
            $this->warn('OPcache reset skipped (function not available in this SAPI).');
        }

        $this->info('VKPOS cache clear complete. Uploads and database were not modified.');

        return 0;
    }
}
