<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TeamBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        Filament::setCurrentPanel('admin');
    }

    public function test_team_block_shows_any_number_of_members_after_other_blocks(): void
    {
        $about = Page::where('slug', 'about')->first();
        $about->sections()->create(['type' => 'image_text', 'label' => 'Our Approach', 'heading' => 'Approach block', 'image_path' => 'pages/a.jpg', 'sort_order' => 1]);
        Storage::disk('public')->put('pages/team/anu.jpg', 'x');
        $about->sections()->create(['type' => 'team', 'heading' => 'Our Team', 'sort_order' => 2, 'items' => [
            ['name' => 'Anu Thomas', 'position' => 'Lead photographer', 'photo' => 'pages/team/anu.jpg'],
            ['name' => 'Rahul Nair', 'position' => 'Cinematographer'],
            ['name' => 'Meera', 'position' => null],
        ]]);
        $about->sections()->create(['type' => 'cta', 'heading' => 'Lets plan', 'sort_order' => 3]);

        $html = $this->get('/about')->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'class="team__card"'));
        foreach (['Anu Thomas', 'Lead photographer', 'Rahul Nair', 'Cinematographer', 'Meera', '>RN<'] as $t) {
            $this->assertStringContainsString($t, $html, $t);
        }
        $this->assertTrue(strpos($html, 'Approach block') < strpos($html, 'Our Team') && strpos($html, 'Our Team') < strpos($html, 'Lets plan'));
    }

    public function test_empty_team_block_renders_nothing(): void
    {
        Page::where('slug', 'about')->first()->sections()->create(['type' => 'team', 'heading' => 'Hidden team', 'items' => []]);
        $this->get('/about')->assertOk()->assertDontSee('Hidden team');
    }

    public function test_admin_adds_team_members_and_removed_photos_are_deleted(): void
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole('super-admin');
        $this->actingAs($u);
        $about = Page::where('slug', 'about')->first();

        Livewire::test(EditPage::class, ['record' => $about->getRouteKey()])
            ->fillForm(['sections' => [['type' => 'team', 'is_visible' => true, 'heading' => 'Our Team', 'items' => [
                ['name' => 'Anu', 'position' => 'Photographer', 'photo' => [UploadedFile::fake()->image('a.jpg', 400, 400)]],
                ['name' => 'Rahul', 'position' => 'Editor'],
            ]]]])
            ->call('save')->assertHasNoFormErrors();

        $block = PageSection::where('type', 'team')->firstOrFail();
        $this->assertSame(['Anu', 'Rahul'], array_column($block->items, 'name'));
        $photo = $block->items[0]['photo'];
        Storage::disk('public')->assertExists($photo);
        $this->get('/about')->assertOk()->assertSee('Anu')->assertSee('Editor');

        $block->update(['items' => [['name' => 'Rahul', 'position' => 'Editor']]]);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_blocks_can_be_moved_up_and_down_with_buttons(): void
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole('super-admin');
        $this->actingAs($u);
        $about = Page::where('slug', 'about')->first();
        $a = $about->sections()->create(['type' => 'text', 'heading' => 'First block', 'sort_order' => 1]);
        $b = $about->sections()->create(['type' => 'text', 'heading' => 'Second block', 'sort_order' => 2]);

        $lw = Livewire::test(EditPage::class, ['record' => $about->getRouteKey()]);
        $keys = array_keys($lw->get('data.sections'));
        $lw->assertSeeHtml('wire:click')->assertSee('Move up', false);
        // move the second block up, then save
        $lw->callAction(\Filament\Actions\Testing\TestAction::make('moveUp')->schemaComponent('sections')->arguments(['item' => $keys[1]]))
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame(['Second block', 'First block'], $about->sections()->orderBy('sort_order')->pluck('heading')->all());
        $html = $this->get('/about')->getContent();
        $this->assertLessThan(strpos($html, 'First block'), strpos($html, 'Second block'));
    }
}
