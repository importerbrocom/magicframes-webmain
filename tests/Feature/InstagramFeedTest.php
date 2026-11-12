<?php

namespace Tests\Feature;

use App\Models\HomeSection;
use App\Models\Setting;
use App\Services\InstagramFeed;
use Database\Seeders\RolesAndAdminSeeder;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstagramFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->seed([RolesAndAdminSeeder::class, SiteContentSeeder::class]);
        HomeSection::where('key', 'social')->update(['is_visible' => true]);
        Setting::setMany(['social.instagram' => 'https://www.instagram.com/magicframes.co.in/']);
    }

    private function media(): array
    {
        return ['data' => [
            ['id' => '11', 'media_type' => 'IMAGE', 'media_url' => 'https://scontent.cdninstagram.com/a.jpg', 'permalink' => 'https://www.instagram.com/p/AA/', 'caption' => 'Wedding day'],
            ['id' => '22', 'media_type' => 'VIDEO', 'media_url' => 'https://scontent.cdninstagram.com/v.mp4', 'thumbnail_url' => 'https://scontent.cdninstagram.com/t.jpg', 'permalink' => 'https://www.instagram.com/reel/BB/'],
            ['id' => '33', 'media_type' => 'IMAGE', 'media_url' => 'https://evil.example.com/x.jpg', 'permalink' => 'https://www.instagram.com/p/CC/'],
        ]];
    }

    public function test_instagram_login_token_reads_graph_instagram(): void
    {
        Http::fake([
            'graph.instagram.com/refresh_access_token*' => Http::response(['access_token' => 'IGAAnew']),
            'graph.instagram.com/me/media*' => Http::response($this->media()),
            'scontent.cdninstagram.com/*' => Http::response(str_repeat('x', 900)),
        ]);
        $feed = app(InstagramFeed::class);
        $feed->saveToken('IGAAtoken');
        $r = $feed->sync();
        $this->assertTrue($r['ok'], $r['message']);
        $this->assertSame(2, $r['count']);
        $this->assertSame(2, substr_count($this->get('/')->assertOk()->getContent(), 'class="ig-tile"'));
    }

    public function test_unified_login_system_user_token_reads_graph_facebook_with_account_id(): void
    {
        Http::fake([
            'graph.facebook.com/v23.0/me/accounts*' => Http::response(['data' => []]),
            'graph.facebook.com/v23.0/17841405012112577/media*' => Http::response($this->media()),
            'scontent.cdninstagram.com/*' => Http::response(str_repeat('x', 900)),
            '*' => Http::response(['error' => ['message' => 'nope']], 400),
        ]);
        $feed = app(InstagramFeed::class);
        $feed->saveToken('EAAsystemtoken');
        Setting::setMany(['instagram.account_id' => '17841405012112577']);
        $r = $feed->sync();
        $this->assertTrue($r['ok'], $r['message']);
        $this->assertSame(2, $r['count']);
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'refresh_access_token'));
        $this->assertSame(2, substr_count($this->get('/')->assertOk()->getContent(), 'class="ig-tile"'));
    }

    public function test_errors_keep_last_good_posts(): void
    {
        Http::fake([
            'graph.facebook.com/v23.0/me/accounts*' => Http::response(['data' => []]),
            'graph.facebook.com/v23.0/17841405012112577/media*' => Http::sequence()->push($this->media())->push(['error' => ['message' => 'Invalid token']], 400),
            'graph.instagram.com/*' => Http::response(['error' => ['message' => 'Other']], 400),
            'scontent.cdninstagram.com/*' => Http::response(str_repeat('x', 900)),
        ]);
        Setting::setMany(['instagram.account_id' => '17841405012112577']);
        $feed = app(InstagramFeed::class);
        $feed->saveToken('EAAsystemtoken');
        $this->assertTrue($feed->sync()['ok']);
        $r = $feed->sync();
        $this->assertFalse($r['ok']);
        $this->assertStringStartsWith('Invalid token', $r['message']);
        $this->assertSame(2, substr_count($this->get('/')->getContent(), 'class="ig-tile"'));
    }

    public function test_falls_back_to_instagram_api_when_facebook_graph_refuses(): void
    {
        // what the client saw: graph.facebook.com answers "#12 singular published story API is deprecated"
        Http::fake([
            'graph.facebook.com/v23.0/me/accounts*' => Http::response(['error' => ['message' => 'no pages']], 400),
            'graph.facebook.com/*' => Http::response(['error' => ['message' => '(#12) singular published story API is deprecated for versions v2.4 and higher']], 400),
            'graph.instagram.com/v23.0/17841405012112577/media*' => Http::response($this->media()),
            'scontent.cdninstagram.com/*' => Http::response(str_repeat('x', 900)),
        ]);
        Setting::setMany(['instagram.account_id' => '17841405012112577']);
        $feed = app(InstagramFeed::class);
        $feed->saveToken('EAAsystemtoken');
        $r = $feed->sync();
        $this->assertTrue($r['ok'], $r['message']);
        $this->assertSame(2, $r['count']);
    }

    public function test_discovers_account_id_from_pages_when_empty(): void
    {
        Http::fake([
            'graph.facebook.com/v23.0/me/accounts*' => Http::response(['data' => [['instagram_business_account' => ['id' => '17899999999999999']]]]),
            'graph.facebook.com/v23.0/17899999999999999/media*' => Http::response($this->media()),
            'scontent.cdninstagram.com/*' => Http::response(str_repeat('x', 900)),
        ]);
        $feed = app(InstagramFeed::class);
        $feed->saveToken('EAAsystemtoken');
        $this->assertTrue($feed->sync()['ok']);
        $this->assertSame('17899999999999999', Setting::get('instagram.account_id'));
    }

    public function test_all_ways_failing_gives_a_helpful_message(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => '(#12) singular published story API is deprecated for versions v2.4 and higher']], 400)]);
        Setting::setMany(['instagram.account_id' => '17841405012112577']);
        $feed = app(InstagramFeed::class);
        $feed->saveToken('EAAsystemtoken');
        $r = $feed->sync();
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Instagram accounts', $r['message']);
    }
}
