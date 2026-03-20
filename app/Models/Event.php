<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasSlug, InteractsWithMedia, Searchable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group_id',
        'created_by',
        'name',
        'slug',
        'description',
        'description_html',
        'event_type',
        'status',
        'starts_at',
        'ends_at',
        'timezone',
        'venue_name',
        'venue_address',
        'venue_latitude',
        'venue_longitude',
        'online_link',
        'cover_photo_path',
        'rsvp_limit',
        'guest_limit',
        'rsvp_opens_at',
        'rsvp_closes_at',
        'is_chat_enabled',
        'is_comments_enabled',
        'cancelled_at',
        'cancellation_reason',
        'series_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'venue_latitude' => 'decimal:7',
            'venue_longitude' => 'decimal:7',
            'rsvp_limit' => 'integer',
            'guest_limit' => 'integer',
            'rsvp_opens_at' => 'datetime',
            'rsvp_closes_at' => 'datetime',
            'is_chat_enabled' => 'boolean',
            'is_comments_enabled' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->extraScope(fn (Builder $builder) => $builder->where('group_id', $this->group_id));
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function hosts(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_hosts')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Rsvp, $this>
     */
    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<EventChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(EventChatMessage::class);
    }

    /**
     * @return HasMany<Feedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * @return BelongsTo<EventSeries, $this>
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(EventSeries::class, 'series_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('local')
            ->storeConversionsOnDisk('public');
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
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())
            ->where('status', EventStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now());
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Cancelled);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm = 50): Builder
    {
        $effectiveLat = 'COALESCE(events.venue_latitude, groups.latitude)';
        $effectiveLng = 'COALESCE(events.venue_longitude, groups.longitude)';

        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians($effectiveLat))
            * cos(radians($effectiveLng) - radians(?)) + sin(radians(?))
            * sin(radians($effectiveLat))))";

        return $query
            ->join('groups', 'events.group_id', '=', 'groups.id')
            ->where(function (Builder $q): void {
                $q->where(function (Builder $sub): void {
                    $sub->whereNotNull('events.venue_latitude')
                        ->whereNotNull('events.venue_longitude');
                })->orWhere(function (Builder $sub): void {
                    $sub->whereNotNull('groups.latitude')
                        ->whereNotNull('groups.longitude');
                });
            })
            ->whereRaw("$haversine < ?", [$lat, $lng, $lat, $radiusKm])
            ->orderByRaw("$haversine", [$lat, $lng, $lat])
            ->select('events.*');
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
            'venue_name' => $this->venue_name,
        ];
    }
}
