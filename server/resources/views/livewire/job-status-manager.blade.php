<div>
    @php
        $swatch = [
            'gray' => '#6b7280', 'primary' => '#2563eb', 'info' => '#0ea5e9',
            'success' => '#059669', 'warning' => '#d97706', 'danger' => '#dc2626',
        ];
    @endphp

    @if(session('job-status-error'))
        <div style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('job-status-error') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <p style="font-size: 13px; color: #6b7280;">Manage the statuses a job can have. Core statuses can be renamed/recolored but not deleted.</p>
        <button wire:click="openCreate" type="button" style="padding: 8px 16px; font-size: 13px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            + New Status
        </button>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Label</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Key</th>
                    <th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Color</th>
                    <th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Jobs</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statuses as $status)
                    <tr style="border-top: 1px solid #f3f4f6;">
                        <td style="padding: 12px 16px;">
                            <span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #fff; background: {{ $swatch[$status['color']] ?? '#6b7280' }};">{{ $status['label'] }}</span>
                            @if($status['is_protected'])
                                <span style="margin-left: 6px; font-size: 11px; color: #9ca3af;">core</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; color: #6b7280; font-family: monospace; font-size: 12px;">{{ $status['name'] }}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            <span style="display: inline-block; width: 14px; height: 14px; border-radius: 4px; background: {{ $swatch[$status['color']] ?? '#6b7280' }};"></span>
                        </td>
                        <td style="padding: 12px 16px; text-align: center; color: #6b7280;">{{ $status['jobs_count'] }}</td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <button wire:click="openEdit({{ $status['id'] }})" type="button" style="padding: 4px 10px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; color: #374151; margin-right: 4px;">Edit</button>
                            @if(! $status['is_protected'] && $status['jobs_count'] === 0)
                                <button wire:click="deleteStatus({{ $status['id'] }})" wire:confirm="Delete this status?" type="button" style="padding: 4px 10px; font-size: 12px; border: 1px solid #fecaca; border-radius: 6px; background: #fff; cursor: pointer; color: #dc2626;">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 24px; text-align: center; color: #9ca3af;">No statuses defined.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showForm)
        <div wire:click.self="closeForm" style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
            <div style="width: 100%; max-width: 400px; margin: 0 16px; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">{{ $editingId ? 'Edit Status' : 'New Status' }}</h3>
                    <button wire:click="closeForm" type="button" style="color: #9ca3af; font-size: 20px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Status Label *</label>
                        <input wire:model="formLabel" type="text" placeholder="e.g. On Hold" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Color</label>
                        <select wire:model="formColor" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; background: #fff;">
                            @foreach(\App\Models\JobStatus::COLORS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                    <button wire:click="closeForm" type="button" style="padding: 9px 18px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;">Cancel</button>
                    <button wire:click="saveStatus" type="button" style="padding: 9px 18px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">{{ $editingId ? 'Update' : 'Create' }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
