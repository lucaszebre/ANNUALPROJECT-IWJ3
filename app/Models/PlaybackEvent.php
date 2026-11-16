<?php

namespace App\Models;

use App\Enums\PlaybackEventType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PlaybackEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'video_id', 'user_id', 'playback_session', 'event', 'position_seconds', 'occurred_at'])]
class PlaybackEvent extends Model
{
    /** @use HasFactory<PlaybackEventFactory> */
    use BelongsToOrganization, HasFactory;

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'event' => PlaybackEventType::class,
            'position_seconds' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
