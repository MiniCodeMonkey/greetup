<?php

namespace App\Models;

use App\Enums\GroupVisibility;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Tags\HasTags;

class Group extends Model implements HasMedia
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory, HasSlug, HasTags, InteractsWithMedia, Searchable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'description_html',
        'organizer_id',
        'location',
        'latitude',
        'longitude',
        'timezone',
        'cover_photo_path',
        'visibility',
        'requires_approval',
        'max_members',
        'welcome_message',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'visibility' => GroupVisibility::class,
            'requires_approval' => 'boolean',
            'max_members' => 'integer',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->using(GroupMember::class)
            ->withPivot('role', 'joined_at', 'is_banned', 'banned_at', 'banned_reason')
            ->withTimestamps();
    }

    /**
     * @return HasMany<GroupMembershipQuestion, $this>
     */
    public function membershipQuestions(): HasMany
    {
        return $this->hasMany(GroupMembershipQuestion::class);
    }

    /**
     * @return HasMany<GroupJoinRequest, $this>
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(GroupJoinRequest::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return HasMany<Discussion, $this>
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->width(400)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('header')
            ->width(1200)
            ->height(400)
            ->sharpen(10)
            ->nonQueued();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', GroupVisibility::Public);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm = 50): Builder
    {
        $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(latitude))
            * cos(radians(longitude) - radians(?)) + sin(radians(?))
            * sin(radians(latitude))))';

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("$haversine < ?", [$lat, $lng, $lat, $radiusKm])
            ->orderByRaw("$haversine", [$lat, $lng, $lat]);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->location,
        ];
    }
}
