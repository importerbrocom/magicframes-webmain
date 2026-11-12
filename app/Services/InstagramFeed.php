<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Latest Instagram posts for the home page, via the official Instagram API.
 * Two token types work: Instagram Login tokens (IGAA…, read from graph.instagram.com/me) and
 * Meta "Unified login" / system-user tokens (EAA…, read from graph.facebook.com/{instagram account id}).
 * Needs an access token of a Business / Creator account (Admin > Site settings > Instagram feed).
 *
 * The feed is saved to storage (JSON + local copies of the pictures), so visitors never wait for
 * Instagram and the site keeps showing the last good posts if Instagram is unreachable.
 */
class InstagramFeed
{
    private const API = 'https://graph.instagram.com';

    private const FB_API = 'https://graph.facebook.com';

    private const REFRESH_AFTER = 3600;        // look for new posts at most once an hour

    private const RETRY_AFTER = 900;           // after a failed attempt wait 15 minutes

    public function token(): ?string
    {
        $enc = Setting::get('instagram.token_enc');
        if (! $enc) {
            return null;
        }
        try {
            return Crypt::decryptString($enc);
        } catch (Throwable) {
            return null;
        }
    }

    public function saveToken(?string $token): void
    {
        Setting::setMany([
            'instagram.token_enc' => $token ? Crypt::encryptString(trim($token)) : null,
            'instagram.token_refreshed' => $token ? now()->toDateString() : null,
        ]);
        if (! $token) {
            $this->clear();
        }
    }

    private const GRAPH_VERSION = 'v23.0';

    /**
     * @param  list<string>  $errors  collects Meta's answers for a helpful message
     */
    private function fetchWithFacebookToken(string $token, string $fields, int $timeout, ?array &$errors): ?\Illuminate\Http\Client\Response
    {
        $errors = [];
        $v = self::GRAPH_VERSION;
        $q = ['fields' => $fields, 'limit' => 12, 'access_token' => $token];
        $ids = array_values(array_unique(array_filter([$this->accountId(), $this->discoverAccountId($token, $timeout)])));

        $urls = [];
        foreach ($ids as $id) {
            $urls[] = self::FB_API."/{$v}/{$id}/media";          // Instagram Graph API (business account id)
            $urls[] = self::API."/{$v}/{$id}/media";             // Instagram API ("Instagram Login compatible")
        }
        $urls[] = self::API."/{$v}/me/media";                    // token that belongs to the Instagram account itself

        foreach ($urls as $url) {
            $res = Http::timeout($timeout)->acceptJson()->get($url, $q);
            if ($res->successful() && is_array($res->json('data'))) {
                if ($ids && ! $this->accountId()) {
                    Setting::setMany(['instagram.account_id' => $ids[0]]);
                }

                return $res;
            }
            $errors[] = (string) ($res->json('error.message') ?: 'HTTP '.$res->status());
        }

        return null;
    }

    /** Find the Instagram business account linked to the token (via its Facebook Pages), if Meta allows it. */
    private function discoverAccountId(string $token, int $timeout): ?string
    {
        try {
            $res = Http::timeout($timeout)->acceptJson()->get(self::FB_API.'/'.self::GRAPH_VERSION.'/me/accounts', [
                'fields' => 'instagram_business_account', 'access_token' => $token,
            ]);
            foreach ((array) $res->json('data', []) as $page) {
                if ($id = $page['instagram_business_account']['id'] ?? null) {
                    return preg_replace('/\D/', '', (string) $id) ?: null;
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    /** @param list<string> $errors */
    private function explain(array $errors): string
    {
        $first = $errors[0] ?? 'Instagram did not return any posts.';

        return $first.' — Check in Meta Business settings (business.facebook.com > Settings > Accounts > Instagram accounts) that magicframes.co.in is added to the Magic Frames portfolio and assigned to the app, then generate a new token.';
    }

    /** Instagram account ID (only needed for Meta Unified login / system-user tokens). */
    public function accountId(): ?string
    {
        $id = preg_replace('/\D/', '', (string) Setting::get('instagram.account_id'));

        return $id !== '' ? $id : null;
    }

    public function isFacebookToken(string $token): bool
    {
        return str_starts_with($token, 'EAA');
    }

    public function limit(): int
    {
        return max(3, min(12, (int) Setting::get('instagram.limit', '8')));
    }

    /** @return list<array{image:string,permalink:string,caption:string,video:bool}> */
    public function posts(): array
    {
        if (! $this->token()) {
            return [];
        }
        $data = $this->read();
        $age = time() - (int) ($data['fetched_at'] ?? 0);
        $sinceTry = time() - (int) ($data['attempted_at'] ?? 0);
        if ((! $data || $age > self::REFRESH_AFTER) && $sinceTry > self::RETRY_AFTER) {
            $this->sync(4);                    // short timeout: never hold up a visitor for long
            $data = $this->read();
        }

        return array_slice($data['posts'] ?? [], 0, $this->limit());
    }

    /** @return array{ok:bool,message:string,count:int} */
    public function sync(int $timeout = 12): array
    {
        $token = $this->token();
        if (! $token) {
            return ['ok' => false, 'message' => 'No Instagram access token saved.', 'count' => 0];
        }
        $data = $this->read();
        $data['attempted_at'] = time();
        $this->write($data);

        try {
            $fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp';
            if ($this->isFacebookToken($token)) {
                // Meta Unified login / system-user token (never expires). Meta answers differently depending on
                // the token and account setup, so try the known ways in turn and use the first that works.
                $res = $this->fetchWithFacebookToken($token, $fields, $timeout, $errors);
                if (! $res) {
                    return $this->fail($data, $this->explain($errors));
                }
            } else {
                $this->maybeRefreshToken($token, $timeout);
                $res = Http::timeout($timeout)->acceptJson()->get(self::API.'/me/media', [
                    'fields' => $fields, 'limit' => 12, 'access_token' => $this->token(),
                ]);
            }
            if (! $res->successful()) {
                $msg = $res->json('error.message') ?: 'Instagram answered with an error ('.$res->status().').';

                return $this->fail($data, $msg);
            }

            $posts = [];
            $keep = [];
            foreach ((array) $res->json('data', []) as $m) {
                $type = $m['media_type'] ?? '';
                $src = $type === 'VIDEO' ? ($m['thumbnail_url'] ?? null) : ($m['media_url'] ?? null);
                $id = preg_replace('/[^0-9A-Za-z_-]/', '', (string) ($m['id'] ?? ''));
                $permalink = (string) ($m['permalink'] ?? '');
                if (! $src || ! $id || ! $this->trustedImageHost($src) || ! str_starts_with($permalink, 'https://www.instagram.com/')) {
                    continue;
                }
                $path = "instagram/$id.jpg";
                $disk = Storage::disk('public');
                if (! $disk->exists($path)) {
                    $img = Http::timeout($timeout)->get($src);
                    if (! $img->successful() || strlen($img->body()) < 500) {
                        continue;
                    }
                    $disk->put($path, $img->body());
                }
                $keep[] = $path;
                $posts[] = [
                    'image' => $path,
                    'permalink' => $permalink,
                    'caption' => mb_substr(trim(preg_replace('/\s+/', ' ', (string) ($m['caption'] ?? ''))), 0, 140),
                    'video' => $type === 'VIDEO',
                ];
            }

            foreach (Storage::disk('public')->files('instagram') as $file) {   // drop pictures of posts no longer in the feed
                if (! in_array($file, $keep, true)) {
                    Storage::disk('public')->delete($file);
                }
            }
            $this->write(['fetched_at' => time(), 'attempted_at' => time(), 'error' => null, 'posts' => $posts]);

            return ['ok' => true, 'message' => count($posts).' latest posts loaded from Instagram.', 'count' => count($posts)];
        } catch (Throwable $e) {
            return $this->fail($data, 'Could not reach Instagram: '.$e->getMessage());
        }
    }

    public function clear(): void
    {
        Storage::disk('local')->delete('instagram-feed.json');
        Storage::disk('public')->deleteDirectory('instagram');
    }

    public function lastError(): ?string
    {
        return $this->read()['error'] ?? null;
    }

    /** Public URL of a saved picture. */
    public static function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /** Long-lived tokens last 60 days; renew them once they are 30+ days old. */
    private function maybeRefreshToken(string $token, int $timeout): void
    {
        $since = Setting::get('instagram.token_refreshed');
        if ($since && now()->diffInDays(\Illuminate\Support\Carbon::parse($since)) < 30) {
            return;
        }
        $res = Http::timeout($timeout)->acceptJson()->get(self::API.'/refresh_access_token', [
            'grant_type' => 'ig_refresh_token', 'access_token' => $token,
        ]);
        if ($res->successful() && $res->json('access_token')) {
            $this->saveToken($res->json('access_token'));
        }
    }

    private function trustedImageHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return str_starts_with($url, 'https://') && (str_ends_with($host, '.cdninstagram.com') || str_ends_with($host, '.fbcdn.net'));
    }

    private function fail(array $data, string $message): array
    {
        $data['error'] = $message;
        $this->write($data);

        return ['ok' => false, 'message' => $message, 'count' => count($data['posts'] ?? [])];
    }

    private function read(): array
    {
        $disk = Storage::disk('local');

        return $disk->exists('instagram-feed.json') ? (json_decode($disk->get('instagram-feed.json'), true) ?: []) : [];
    }

    private function write(array $data): void
    {
        Storage::disk('local')->put('instagram-feed.json', json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
