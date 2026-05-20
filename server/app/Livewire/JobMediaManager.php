<?php

namespace App\Livewire;

use App\Models\Job;
use App\Models\JobMedia;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class JobMediaManager extends Component
{
    use WithFileUploads;

    public Job $job;

    public array $uploads = [];
    public bool $showUploadModal = false;
    public ?int $confirmDeleteId = null;

    public function mount(Job $job): void
    {
        $this->job = $job;
    }

    public function getMediaProperty()
    {
        return $this->job->media()->latest()->get();
    }

    public function openUploadModal(): void
    {
        $this->uploads = [];
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->uploads = [];
    }

    public function save(): void
    {
        $this->validate([
            'uploads.*' => 'required|file|max:51200', // 50MB max per file
        ]);

        foreach ($this->uploads as $file) {
            $mime = $file->getMimeType();
            $type = match (true) {
                str_starts_with($mime, 'image/') => 'photo',
                str_starts_with($mime, 'video/') => 'video',
                default => 'document',
            };

            $path = $file->store("job-media/{$this->job->id}", 'public');

            JobMedia::create([
                'job_id' => $this->job->id,
                'uploaded_by' => auth()->id(),
                'filename' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'size' => $file->getSize(),
                'disk' => 'public',
                'path' => $path,
                'type' => $type,
            ]);
        }

        $this->uploads = [];
        $this->showUploadModal = false;
        $this->job->refresh();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function deleteMedia(int $id): void
    {
        $media = JobMedia::where('id', $id)
            ->where('job_id', $this->job->id)
            ->first();

        if ($media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        $this->confirmDeleteId = null;
        $this->job->refresh();
    }

    public function render()
    {
        return view('livewire.job-media-manager');
    }
}
