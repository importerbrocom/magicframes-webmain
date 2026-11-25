<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client request: remove the Portfolio block (photos + "View All Galleries") from the home page.
 * The block is only switched off, nothing is deleted: Admin > Homepage > Home sections > Portfolio > Visible brings it back.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('home_sections')->where('key', 'portfolio')->update(['is_visible' => false]);
        \App\Services\ContentCache::bump();
    }

    public function down(): void
    {
        DB::table('home_sections')->where('key', 'portfolio')->update(['is_visible' => true]);
    }
};
