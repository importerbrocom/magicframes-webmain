<?php

namespace Tests\Feature;

use App\Filament\Resources\Enquiries\Pages\EditEnquiry;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Filament\Widgets\EnquiryStats;
use App\Mail\NewEnquiryMail;
use App\Models\Enquiry;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndAdminSeeder::class);
        Filament::setCurrentPanel('admin');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Aarav Sharma',
            'email' => 'aarav@example.test',
            'phone' => '+91 98765 43210',
            'event_type' => 'Wedding',
            'event_date' => now()->addMonths(6)->format('Y-m-d'),
            'message' => 'We would love to book you for our wedding.',
        ], $override);
    }

    private function login(string $role = 'super-admin'): User
    {
        $u = User::factory()->create(['is_active' => true]);
        $u->assignRole($role);
        $this->actingAs($u);

        return $u;
    }

    public function test_visitor_can_submit_enquiry(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/enquiries', $this->payload(['source_page' => '/contact']))
            ->assertCreated()->assertJsonPath('message', 'Thank you! We will get back to you soon.');

        $e = Enquiry::firstOrFail();
        $this->assertSame('Aarav Sharma', $e->name);
        $this->assertSame('aarav@example.test', $e->email);
        $this->assertSame('Wedding', $e->event_type);
        $this->assertSame(now()->addMonths(6)->format('Y-m-d'), $e->event_date->format('Y-m-d'));
        $this->assertSame('new', $e->status);
        $this->assertNotNull($e->ip_address);
        $this->assertSame('/contact', $e->source_page);
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $this->postJson('/api/v1/enquiries', ['name' => 'Mira', 'email' => 'mira@example.test', 'message' => 'Hello, are you free in June?'])->assertCreated();
        $this->assertNull(Enquiry::first()->phone);
    }

    public function test_validation_errors(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->postJson('/api/v1/enquiries', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'message']);
        $this->postJson('/api/v1/enquiries', $this->payload(['email' => 'nope']))->assertJsonValidationErrors(['email']);
        $this->postJson('/api/v1/enquiries', $this->payload(['phone' => 'abc<script>']))->assertJsonValidationErrors(['phone']);
        $this->postJson('/api/v1/enquiries', $this->payload(['message' => 'short']))->assertJsonValidationErrors(['message']);
        $this->postJson('/api/v1/enquiries', $this->payload(['message' => str_repeat('a', 3001)]))->assertJsonValidationErrors(['message']);
        $this->postJson('/api/v1/enquiries', $this->payload(['event_date' => '2020-01-01']))->assertJsonValidationErrors(['event_date']);
        $this->postJson('/api/v1/enquiries', $this->payload(['event_date' => 'tomorrow-ish']))->assertJsonValidationErrors(['event_date']);
        $this->postJson('/api/v1/enquiries', $this->payload(['event_type' => 'Hacking']))->assertJsonValidationErrors(['event_type']);
        $this->assertSame(0, Enquiry::count());
    }

    public function test_html_is_stripped_from_stored_text(): void
    {
        $this->postJson('/api/v1/enquiries', $this->payload([
            'name' => '<b>Evil</b> Name', 'message' => '<script>alert(1)</script>Please call me about June',
        ]))->assertCreated();
        $e = Enquiry::first();
        $this->assertSame('Evil Name', $e->name);
        $this->assertStringNotContainsString('<script', $e->message);
        $this->assertStringContainsString('Please call me', $e->message);
    }

    public function test_honeypot_silently_discards_bot_submissions(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/enquiries', $this->payload(['website' => 'http://spam.example']))->assertCreated();
        $this->assertSame(0, Enquiry::count());
        Mail::assertNothingSent();
    }

    public function test_submissions_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/enquiries', $this->payload())->assertCreated();
        }
        $this->postJson('/api/v1/enquiries', $this->payload())->assertStatus(429);
        $this->assertSame(5, Enquiry::count());
    }

    public function test_email_notification_goes_to_studio_address(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/enquiries', $this->payload())->assertCreated();
        Mail::assertNothingSent(); // no recipient configured yet

        Setting::setMany(['contact.email' => 'studio@example.test']);
        $this->postJson('/api/v1/enquiries', $this->payload(['name' => 'Second Person']))->assertCreated();
        Mail::assertSent(NewEnquiryMail::class, fn ($m) => $m->hasTo('studio@example.test') && $m->enquiry->name === 'Second Person');
    }

    public function test_mail_failure_does_not_lose_the_enquiry(): void
    {
        Setting::setMany(['contact.email' => 'studio@example.test']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson('/api/v1/enquiries', $this->payload())->assertCreated();
        $this->assertSame(1, Enquiry::count());
    }

    public function test_mail_body_renders(): void
    {
        $e = Enquiry::create(['name' => 'A', 'email' => 'a@example.test', 'message' => 'Hello there friend']);
        $html = (new NewEnquiryMail($e))->render();
        $this->assertStringContainsString('Hello there friend', $html);
        $this->assertStringContainsString('/admin/enquiries/'.$e->id, $html);
    }

    public function test_enquiry_options_endpoint(): void
    {
        $this->getJson('/api/v1/enquiry-options')->assertOk()->assertJsonPath('data.event_types.0', 'Wedding');
    }

    public function test_admin_inbox_lists_filters_and_searches(): void
    {
        $this->login();
        $a = Enquiry::create(['name' => 'Alpha Client', 'email' => 'alpha@example.test', 'message' => 'Message one here', 'event_type' => 'Wedding']);
        $b = Enquiry::create(['name' => 'Beta Client', 'email' => 'beta@example.test', 'message' => 'Message two here', 'event_type' => 'Portrait', 'status' => 'completed']);

        Livewire::test(ListEnquiries::class)
            ->assertCanSeeTableRecords([$a, $b])
            ->searchTable('Alpha')->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b])
            ->searchTable('')
            ->filterTable('status', ['completed'])->assertCanSeeTableRecords([$b])->assertCanNotSeeTableRecords([$a])
            ->removeTableFilters()
            ->filterTable('event_type', 'Wedding')->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b]);
    }

    public function test_status_workflow_from_table_edit_and_bulk(): void
    {
        $this->login();
        $e = Enquiry::create(['name' => 'Gamma', 'email' => 'g@example.test', 'message' => 'Message three here']);
        $this->assertNull($e->status_changed_at);

        // inline status dropdown in the table
        Livewire::test(ListEnquiries::class)->call('updateTableColumnState', 'status', $e->getKey(), 'contacted');
        $e->refresh();
        $this->assertSame('contacted', $e->status);
        $this->assertNotNull($e->status_changed_at);

        // edit page: status + notes
        Livewire::test(EditEnquiry::class, ['record' => $e->getRouteKey()])
            ->fillForm(['status' => 'in_progress', 'admin_notes' => 'Called, sending quote'])
            ->call('save')->assertHasNoFormErrors();
        $e->refresh();
        $this->assertSame('in_progress', $e->status);
        $this->assertSame('Called, sending quote', $e->admin_notes);

        Livewire::test(EditEnquiry::class, ['record' => $e->getRouteKey()])
            ->fillForm(['status' => 'bogus'])->call('save')->assertHasFormErrors(['status']);

        // bulk
        $x = Enquiry::create(['name' => 'X', 'email' => 'x@example.test', 'message' => 'Message four here']);
        Livewire::test(ListEnquiries::class)
            ->selectTableRecords([$e->getKey(), $x->getKey()])
            ->callAction(TestAction::make('mark_completed')->table()->bulk());
        $this->assertSame(['completed', 'completed'], [$e->fresh()->status, $x->fresh()->status]);

        // detail view shows everything the customer sent
        Livewire::test(ViewEnquiry::class, ['record' => $e->getRouteKey()])
            ->assertSee('Gamma')->assertSee('Message three here')->assertSee('Called, sending quote');
    }

    public function test_customer_fields_are_not_editable_and_records_cannot_be_created_in_admin(): void
    {
        $this->login();
        $e = Enquiry::create(['name' => 'Delta', 'email' => 'd@example.test', 'message' => 'Message five here']);

        Livewire::test(EditEnquiry::class, ['record' => $e->getRouteKey()])
            ->fillForm(['status' => 'contacted'])->call('save');
        $this->assertSame('Delta', $e->fresh()->name);
        $this->get('/admin/enquiries/create')->assertNotFound();
    }

    public function test_delete_enquiry(): void
    {
        $this->login();
        $e = Enquiry::create(['name' => 'Del', 'email' => 'd@example.test', 'message' => 'Message six here']);
        Livewire::test(ListEnquiries::class)->callTableAction('delete', $e);
        $this->assertDatabaseMissing('enquiries', ['id' => $e->id]);
    }

    public function test_csv_export_respects_filters_and_neutralises_formulas(): void
    {
        $this->login();
        Enquiry::create(['name' => '=HYPERLINK("http://evil")', 'email' => 'e@example.test', 'message' => 'Formula injection attempt', 'status' => 'new']);
        Enquiry::create(['name' => 'Done Person', 'email' => 'done@example.test', 'message' => 'Already handled one', 'status' => 'completed']);

        $component = Livewire::test(ListEnquiries::class)->filterTable('status', ['new'])->callAction('export');
        $component->assertFileDownloaded('enquiries-'.now()->format('Y-m-d').'.csv');

        $csv = base64_decode($component->effects['download']['content']);
        $this->assertStringStartsWith("\xEF\xBB\xBFReceived,Name,Email", $csv);
        $this->assertStringContainsString("\"'=HYPERLINK", $csv, 'formula cells must be neutralised');
        $this->assertStringNotContainsString('Done Person', $csv, 'export must respect the active filter');
        $this->assertStringContainsString('Formula injection attempt', $csv);
    }

    public function test_permissions_and_dashboard_widget(): void
    {
        Enquiry::create(['name' => 'W', 'email' => 'w@example.test', 'message' => 'Message seven here']);

        $this->login('enquiry-handler');
        $this->get('/admin/enquiries')->assertOk();
        $this->get('/admin')->assertOk();
        $this->get('/admin/gallery-images')->assertForbidden();
        Livewire::test(EnquiryStats::class)->assertSee('Waiting for a reply');

        auth()->logout();
        $this->get('/admin/enquiries')->assertRedirect('/admin/login');

        $this->login('editor');
        $this->get('/admin/enquiries')->assertOk();
    }
}
