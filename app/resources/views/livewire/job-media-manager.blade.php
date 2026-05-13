<div>
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">
                Attachments
                @if($this->media->count())
                    <span style="font-weight: 400; color: #6b7280; font-size: 14px;">({{ $this->media->count() }})</span>
                @endif
            </h3>
            <p style="font-size: 13px; color: #6b7280; margin: 2px 0 0;">Photos, videos, and documents for this job</p>
        </div>
        <button
            wire:click="openUploadModal"
            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #c9092f; color: #fff; font-size: 13px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer;"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
            Upload Files
        </button>
    </div>

    {{-- Media Cards Grid --}}
    @if($this->media->count() > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
            @foreach($this->media as $item)
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; position: relative; transition: box-shadow 0.15s;"
                     onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                     onmouseout="this.style.boxShadow='none'">

                    {{-- Preview --}}
                    <div style="height: 140px; background: #f9fafb; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        @if($item->isImage())
                            <img src="{{ $item->getUrl() }}" alt="{{ $item->original_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @elseif($item->isVideo())
                            <video src="{{ $item->getUrl() }}" style="width: 100%; height: 100%; object-fit: cover;" muted></video>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#9ca3af" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        @endif
                    </div>

                    {{-- Type badge --}}
                    <div style="position: absolute; top: 8px; left: 8px;">
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
                            {{ $item->type === 'photo' ? 'background: #dbeafe; color: #1d4ed8;' : '' }}
                            {{ $item->type === 'video' ? 'background: #fce7f3; color: #be185d;' : '' }}
                            {{ $item->type === 'document' ? 'background: #fef3c7; color: #92400e;' : '' }}
                        ">{{ $item->type }}</span>
                    </div>

                    {{-- Info --}}
                    <div style="padding: 12px;">
                        <div style="font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $item->original_name }}">
                            {{ $item->original_name }}
                        </div>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;">
                            {{ $item->getHumanSize() }} &middot; {{ $item->created_at->format('M j, Y') }}
                        </div>

                        {{-- Actions --}}
                        <div style="display: flex; gap: 8px; margin-top: 10px;">
                            <a href="{{ $item->getUrl() }}" target="_blank" download="{{ $item->original_name }}"
                               style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 6px; font-size: 12px; font-weight: 600; color: #374151; background: #f3f4f6; border-radius: 6px; text-decoration: none; border: 1px solid #e5e7eb;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Download
                            </a>
                            @if($confirmDeleteId === $item->id)
                                <button wire:click="deleteMedia({{ $item->id }})"
                                    style="flex: 1; padding: 6px; font-size: 12px; font-weight: 600; color: #fff; background: #dc2626; border: none; border-radius: 6px; cursor: pointer;">
                                    Confirm
                                </button>
                                <button wire:click="cancelDelete"
                                    style="padding: 6px 8px; font-size: 12px; color: #6b7280; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                                    Cancel
                                </button>
                            @else
                                <button wire:click="confirmDelete({{ $item->id }})"
                                    style="padding: 6px 8px; font-size: 12px; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 48px 24px; background: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="#d1d5db" style="width: 48px; height: 48px; margin: 0 auto 12px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
            </svg>
            <p style="font-size: 14px; color: #6b7280; margin: 0;">No attachments yet</p>
            <p style="font-size: 12px; color: #9ca3af; margin: 4px 0 0;">Click "Upload Files" to add photos, videos, or documents</p>
        </div>
    @endif

    {{-- Upload Modal --}}
    @if($showUploadModal)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 16px;" onclick="if(event.target===this)$wire.closeUploadModal()">
            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.4);"></div>
            <div style="position: relative; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); width: 100%; max-width: 520px; overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #111827; margin: 0;">Upload Attachments</h3>
                    <button wire:click="closeUploadModal" style="background: none; border: none; cursor: pointer; color: #6b7280; font-size: 20px; line-height: 1;">&times;</button>
                </div>
                <div style="padding: 24px;">
                    <div
                        x-data="{ isDragging: false }"
                        @dragover.prevent
                        @dragenter.prevent="isDragging = true"
                        @dragleave.prevent="if (!$el.contains($event.relatedTarget)) isDragging = false"
                        @drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $wire.uploadMultiple('uploads', $event.dataTransfer.files) }"
                        :style="`border: 2px dashed ${isDragging ? '#c9092f' : '#d1d5db'}; border-radius: 12px; padding: 32px; text-align: center; background: ${isDragging ? '#fef1f3' : '#f9fafb'}; transition: all 120ms ease;`"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#9ca3af" style="width: 36px; height: 36px; margin: 0 auto 12px; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <p x-show="isDragging" style="font-size: 15px; font-weight: 600; color: #c9092f; margin: 0 0 4px;">Release to upload</p>
                        <p x-show="!isDragging" style="font-size: 14px; font-weight: 500; color: #374151; margin: 0 0 8px;">Drag &amp; drop files here</p>
                        <p x-show="!isDragging" style="font-size: 12px; color: #9ca3af; margin: 0 0 12px;">or click below to browse</p>
                        <input type="file" wire:model="uploads" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                               style="display: block; margin: 0 auto; font-size: 13px; color: #374151;" />
                        <p style="font-size: 11px; color: #9ca3af; margin-top: 12px;">Photos, videos, PDFs, and documents. Max 50MB per file.</p>
                    </div>

                    @error('uploads.*')
                        <p style="color: #dc2626; font-size: 13px; margin-top: 8px;">{{ $message }}</p>
                    @enderror

                    <div wire:loading wire:target="uploads" style="margin-top: 12px; text-align: center; font-size: 13px; color: #6b7280;">
                        Uploading files...
                    </div>

                    @if(count($uploads) > 0)
                        <div style="margin-top: 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px;">
                            <p style="font-size: 13px; font-weight: 600; color: #166534; margin: 0;">{{ count($uploads) }} file(s) ready</p>
                        </div>
                    @endif
                </div>
                <div style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                    <button wire:click="closeUploadModal" style="padding: 8px 16px; font-size: 14px; font-weight: 600; color: #374151; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">Cancel</button>
                    <button wire:click="save" wire:loading.attr="disabled"
                            {{ count($uploads) === 0 ? 'disabled' : '' }}
                            style="padding: 8px 20px; font-size: 14px; font-weight: 600; color: #fff; background: {{ count($uploads) > 0 ? '#059669' : '#9ca3af' }}; border: none; border-radius: 8px; cursor: {{ count($uploads) > 0 ? 'pointer' : 'not-allowed' }};">
                        <span wire:loading.remove wire:target="save">Upload {{ count($uploads) }} File(s)</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
