<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_security_headers_are_sent_on_site_api_and_admin(): void
    {
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        foreach (['/', '/api/v1/site', '/admin/login'] as $url) {
            $this->get($url)
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeaderMissing('Strict-Transport-Security'); // not on plain http
        }
    }

    public function test_hsts_only_on_https_in_production(): void
    {
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        $this->app['env'] = 'production';
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
    }

    public function test_users_can_change_their_own_password_and_reset_page_exists(): void
    {
        $this->seed(RolesAndAdminSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'password' => 'Old-password-123']);
        $user->assignRole('editor');
        $this->actingAs($user);

        $this->get('/admin/profile')->assertOk();
        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Renamed', 'email' => $user->email, 'password' => 'New-password-456', 'passwordConfirmation' => 'New-password-456', 'currentPassword' => 'Old-password-123'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('New-password-456', $user->fresh()->password));

        auth()->logout();
        $this->get('/admin/password-reset/request')->assertOk();
    }

    public function test_admin_shows_the_studio_logo_in_light_and_dark_mode(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();
        $this->assertStringContainsString('images/admin-logo-dark.png', $html);
        $this->assertStringContainsString('images/admin-logo-light.png', $html);
        $this->assertFileExists(public_path('images/admin-logo-dark.png'));
        $this->assertFileExists(public_path('images/admin-logo-light.png'));
    }

    public function test_admin_loads_the_typography_stylesheet(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();
        $this->assertStringContainsString('css/admin-typography.css', $html);
        $this->assertFileExists(public_path('css/admin-typography.css'));
    }
}
