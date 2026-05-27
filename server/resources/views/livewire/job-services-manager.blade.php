<div class="job-services-manager">
    <style>
        .dark .job-services-manager [style*="background: #fff"],
        .dark .job-services-manager [style*="background:#fff"] {
            background: #1f2937 !important;
        }
        .dark .job-services-manager [style*="color: #111827"] {
            color: #f9fafb !important;
        }
        .dark .job-services-manager [style*="color: #6b7280"],
        .dark .job-services-manager [style*="color: #9ca3af"] {
            color: #9ca3af !important;
        }
        .dark .job-services-manager [style*="border: 1px solid #e5e7eb"],
        .dark .job-services-manager [style*="border: 1px solid #d1d5db"],
        .dark .job-services-manager [style*="border-bottom: 1px solid #e5e7eb"],
        .dark .job-services-manager [style*="border-bottom: 1px solid #f3f4f6"] {
            border-color: #374151 !important;
        }
        .dark .job-services-manager input,
        .dark .job-services-manager textarea {
            color: #f9fafb !important;
            background: #111827 !important;
            border-color: #374151 !important;
        }
    </style>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Services</label>
            <span style="font-size: 12px; color: #9ca3af;">
                {{ count($lines) }} {{ \Illuminate\Support\Str::plural('service', count($lines)) }}
            </span>
        </div>

        {{-- Lines --}}
        @if (count($lines) > 0)
            <div style="display: grid; grid-template-columns: 1fr 40px; gap: 8px; padding: 0 0 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 8px;">
                <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Description</span>
                <span></span>
            </div>

            @foreach ($lines as $i => $line)
                <div wire:key="line-{{ $line['id'] }}" style="display: grid; grid-template-columns: 1fr 40px; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                    <input
                        wire:model.blur="lines.{{ $i }}.description"
                        type="text"
                        placeholder="{{ $line['service_name'] ?? 'Custom service description' }}"
                        style="padding: 8px 10px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; width: 100%; box-sizing: border-box;"
                    />
                    <button
                        wire:click="removeLine({{ $i }})"
                        type="button"
                        title="Remove service"
                        style="color: #dc2626; border: none; background: none; cursor: pointer; font-size: 18px; padding: 4px; line-height: 1;"
                    >&times;</button>
                </div>
            @endforeach
        @else
            <div style="padding: 24px 0; text-align: center; color: #9ca3af; font-size: 13px; border-bottom: 1px dashed #e5e7eb;">
                No services attached yet. Search below to add one.
            </div>
        @endif

        {{-- Add line: service search + custom line --}}
        <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
            <div style="position: relative;">
                <input
                    wire:model.live.debounce.300ms="serviceSearch"
                    wire:focus="$set('showServiceDropdown', true)"
                    type="text"
                    placeholder="Search services..."
                    style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"
                />
                @if ($showServiceDropdown && $this->serviceResults->count() > 0)
                    <div style="position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin-top: 4px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 240px; overflow-y: auto;">
                        @foreach ($this->serviceResults as $svc)
                            <button
                                wire:click="addService({{ $svc->id }})"
                                type="button"
                                style="width: 100%; text-align: left; padding: 10px 14px; border: none; background: none; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;"
                                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'"
                            >
                                <span style="font-weight: 500; color: #111827;">{{ $svc->name }}</span>
                                @if ($svc->category)
                                    <span style="font-size: 11px; color: #9ca3af;">{{ $svc->category }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div style="display: flex; gap: 8px;">
                <button
                    wire:click="addCustomLine"
                    type="button"
                    style="padding: 8px 14px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151; white-space: nowrap;"
                >+ Custom Line</button>
            </div>
        </div>
    </div>
</div>
