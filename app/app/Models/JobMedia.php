<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JobMedia extends Model
{
    protected $table = 'job_media';

    protected $fillable = [
        'job_id',
        'uploaded_by',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'disk',
        'path',
        'type',
        'notes',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function getHumanSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }

    public function getIconName(): string
    {
        if ($this->isImage()) return 'photo';
        if ($this->isVideo()) return 'video-camera';

        return match (true) {
            str_contains($this->mime_type, 'pdf') => 'document-text',
            str_contains($this->mime_type, 'spreadsheet'), str_contains($this->mime_type, 'excel') => 'table-cells',
            str_contains($this->mime_type, 'word'), str_contains($this->mime_type, 'document') => 'document',
            default => 'paper-clip',
        };
    }
}
