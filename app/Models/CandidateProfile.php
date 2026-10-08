<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CandidateProfile extends Model
{
    public const STATUS_FOR_ASSESSMENT = 'for_assessment';

    public const STATUS_FOR_EVALUATION = 'for_evaluation';

    public const STATUS_ACTIVE = 'active';

    /** CVs are private: never served from the public disk. */
    public const CV_DISK = 'local';

    protected $fillable = [
        'user_id',
        'location',
        'desired_position',
        'experience_level',
        'work_setup',
        'skills',
        'linkedin_url',
        'cv_path',
        'cv_original_name',
        'cv_size',
        'cv_uploaded_at',
        'status',
        'status_updated_at',
    ];

    protected $hidden = [
        'cv_path',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'cv_size' => 'integer',
            'cv_uploaded_at' => 'datetime',
            'status_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storeCv(UploadedFile $file): void
    {
        $previous = $this->cv_path;

        $this->forceFill([
            'cv_path' => $file->store('cvs', self::CV_DISK),
            'cv_original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'cv_size' => $file->getSize(),
            'cv_uploaded_at' => now(),
        ])->save();

        if ($previous && $previous !== $this->cv_path) {
            Storage::disk(self::CV_DISK)->delete($previous);
        }
    }

    public function hasCv(): bool
    {
        return $this->cv_path !== null && Storage::disk(self::CV_DISK)->exists($this->cv_path);
    }

    /** Public CV metadata (no storage path). */
    public function cvSummary(): ?array
    {
        if (! $this->cv_path) {
            return null;
        }

        return [
            'name' => $this->cv_original_name,
            'size' => $this->cv_size,
            'uploaded_at' => $this->cv_uploaded_at?->toIso8601String(),
        ];
    }
}
