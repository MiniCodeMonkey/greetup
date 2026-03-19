<?php

namespace App\Models;

use Database\Factories\EventSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSeries extends Model
{
    /** @use HasFactory<EventSeriesFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group_id',
        'recurrence_rule',
    ];

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'series_id');
    }
}
