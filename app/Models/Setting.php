<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const string CACHE_KEY = 'platform_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @var array<string, string|null>
     */
    public const array DEFAULTS = [
        'site_name' => 'Greetup',
        'site_description' => '',
        'registration_enabled' => '1',
        'require_email_verification' => '1',
        'max_groups_per_user' => null,
        'default_timezone' => 'UTC',
        'default_locale' => 'en',
        'support_url' => null,
    ];

    /**
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, function (): array {
            $stored = static::query()->pluck('value', 'key')->toArray();

            return array_merge(static::DEFAULTS, $stored);
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = static::allCached();

        return $settings[$key] ?? $default ?? (static::DEFAULTS[$key] ?? null);
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }
}
