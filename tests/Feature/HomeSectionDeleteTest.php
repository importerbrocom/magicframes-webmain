<?php

namespace Tests\Feature;

use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\HomeSection;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class HomeSectionDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_home_section_and_restore_it(): void
    {
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        Filament::setCurrentPanel('admin');
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole('super-admin');
        $this->actingAs($u);

        $about = HomeSection::where('key', 'about')->first();
        Storage::disk('public')->put('home/a.jpg', 'x');
        $about->update(['image_path' => 'home/a.jpg', 'title' => 'About title here']);
        $this->get('/')->assertSee('About title here');

        Livewire::test(ManageHomeSections::class)
            ->assertActionHidden('restore')
            ->callTableAction('delete', $about);

        $this->assertNull(HomeSection::where('key', 'about')->first());
        Storage::disk('public')->assertMissing('home/a.jpg');
        $this->get('/')->assertOk()->assertDontSee('About title here');

        Livewire::test(ManageHomeSections::class)->assertActionVisible('restore')->callAction('restore')->assertNotified();
        $back = HomeSection::where('key', 'about')->first();
        $this->assertNotNull($back);
        $this->assertFalse($back->is_visible);
        $this->assertSame(count(HomeSection::DEFAULTS), HomeSection::count());
    }
}
