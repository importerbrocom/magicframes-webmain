<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** No RefreshDatabase on purpose: these tests point the app at throw-away database settings. */
class BackupTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->tmp = storage_path('framework/testing/backup-'.uniqid());
        File::ensureDirectoryExists($this->tmp);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        File::deleteDirectory(storage_path('app/backups'));
        parent::tearDown();
    }

    public function test_backup_contains_database_and_media_and_rotates(): void
    {
        $db = $this->tmp.'/site.sqlite';
        file_put_contents($db, 'SQLite format 3 fake-db-for-test');
        config(['database.default' => 'bk', 'database.connections.bk' => ['driver' => 'sqlite', 'database' => $db, 'prefix' => '', 'foreign_key_constraints' => false]]);

        // The command reads the real public disk folder, so use the real one and clean up afterwards.
        $public = storage_path('app/public');
        File::ensureDirectoryExists($public.'/gallery');
        File::ensureDirectoryExists($public.'/_cache/800');
        file_put_contents($public.'/gallery/a.jpg', 'IMG');
        file_put_contents($public.'/_cache/800/skip.webp', 'CACHE');

        try {
            $this->artisan('site:backup')->assertSuccessful();
            sleep(1);
            $this->artisan('site:backup', ['--keep' => 1])->assertSuccessful();

            $archives = glob(storage_path('app/backups/magicframes-*.zip'));
            $this->assertCount(1, $archives, 'older archives are removed');
            $zip = new \ZipArchive;
            $zip->open($archives[0]);
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $names[] = $zip->getNameIndex($i);
            }
            $this->assertContains('database.sqlite', $names);
            $this->assertContains('media/gallery/a.jpg', $names);
            $this->assertNotContains('media/_cache/800/skip.webp', $names, 'regenerable cache is excluded');
            $this->assertSame('IMG', $zip->getFromName('media/gallery/a.jpg'));
            $this->assertEmpty(glob(storage_path('app/backups/.work-*')), 'temporary files are cleaned up');
        } finally {
            File::delete([$public.'/gallery/a.jpg', $public.'/_cache/800/skip.webp']);
        }
    }

    public function test_backup_fails_cleanly_without_a_dump_tool(): void
    {
        config([
            'database.default' => 'mysql_x',
            'database.connections.mysql_x' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306, 'database' => 'x', 'username' => 'x', 'password' => 'x'],
            'backup.mysqldump_path' => '/nonexistent/mysqldump',
        ]);
        $this->artisan('site:backup')->assertFailed();
        $this->assertEmpty(glob(storage_path('app/backups/magicframes-*.zip')));
    }

    public function test_backup_is_scheduled_daily(): void
    {
        Artisan::call('schedule:list');
        $this->assertStringContainsString('site:backup', Artisan::output());
    }
}
