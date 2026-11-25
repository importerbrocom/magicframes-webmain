<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client request: no "Captured Moments / Our Portfolio" heading above the home page portfolio photos.
 * Only clears the original default wording; a heading the studio typed itself is left alone.
 * To show a heading again, fill "Small script label" / "Title" in Admin > Homepage > Home sections > Portfolio.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('home_sections')->where('key', 'portfolio')->where('label', 'Captured Moments')->update(['label' => null]);
        DB::table('home_sections')->where('key', 'portfolio')->where('title', 'Our Portfolio')->update(['title' => null]);
        \App\Services\ContentCache::bump();
    }

    public function down(): void
    {
        DB::table('home_sections')->where('key', 'portfolio')->whereNull('label')->update(['label' => 'Captured Moments']);
        DB::table('home_sections')->where('key', 'portfolio')->whereNull('title')->update(['title' => 'Our Portfolio']);
    }
};
