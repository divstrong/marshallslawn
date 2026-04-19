<?php

namespace App\Livewire;

use App\Models\Property;
use App\Models\PropertyMedia;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PropertyMediaManager extends Component
{
    use WithFileUploads;

    public Property $property;

    public array $uploads = [];
    public bool $showUploadModal = false;
    public ?int $confirmDeleteId = null;

    public function mount(Property $property): void
    {
        $this->property = $property;
    }

    public function getMediaProperty()
    {
        return $this->property->media()->latest()->get();
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

            $path = $file->store("property-media/{$this->property->id}", 'public');

            PropertyMedia::create([
                'property_id' => $this->property->id,
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
        $this->property->refresh();
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
        $media = PropertyMedia::where('id', $id)
            ->where('property_id', $this->property->id)
            ->first();

        if ($media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        $this->confirmDeleteId = null;
        $this->property->refresh();
    }

    public function render()
    {
        return view('livewire.property-media-manager');
    }
}
