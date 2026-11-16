<?php

namespace App\Models;

use App\Enums\TranscriptStatus;
use App\Enums\VideoLanguage;
use App\Enums\VideoStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property VideoStatus $status
 * @property VideoLanguage $language
 * @property TranscriptStatus $transcript_status
 */
#[Fillable([
    'organization_id', 'lesson_id', 'status', 'source_path', 'hls_path', 'thumbnail_path',
    'duration_seconds', 'size_bytes', 'language', 'transcript_status', 'ready_at',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function transcriptSegments(): HasMany
    {
        return $this->hasMany(TranscriptSegment::class)->orderBy('start_ms');
    }

    public function playbackEvents(): HasMany
    {
        return $this->hasMany(PlaybackEvent::class);
    }

    /**
     * Videos students may see: transcoding is finished.
     */
    #[Scope]
    protected function ready(Builder $query): void
    {
        $query->where('status', VideoStatus::Ready);
    }

    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
            'language' => VideoLanguage::class,
            'transcript_status' => TranscriptStatus::class,
            'duration_seconds' => 'integer',
            'size_bytes' => 'integer',
            'ready_at' => 'datetime',
        ];
    }
}
