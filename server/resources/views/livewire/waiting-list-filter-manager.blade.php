<div>
    @php $groups = $this->groupOptions(); @endphp

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <p style="font-size: 13px; color: #6b7280;">Manage the service filters shown above the Scheduling &rarr; Waiting List queue.</p>
        <button wire:click="openCreate" type="button" style="padding: 8px 16px; font-size: 13px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            + New Filter
        </button>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Label</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Service Group</th>
                    <th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Active</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filters as $filter)
                    <tr style="border-top: 1px solid #f3f4f6;">
                        <td style="padding: 12px 16px; font-weight: 500;">{{ $filter['label'] }}</td>
                        <td style="padding: 12px 16px; color: #6b7280;">{{ $groups[$filter['service_group']] ?? $filter['service_group'] }}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($filter['is_active'])
                                <span style="color: #059669; font-weight: 600;">Yes</span>
                            @else
                                <span style="color: #9ca3af;">No</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <button wire:click="openEdit({{ $filter['id'] }})" type="button" style="padding: 4px 10px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; color: #374151; margin-right: 4px;">Edit</button>
                            <button wire:click="deleteFilter({{ $filter['id'] }})" wire:confirm="Delete this filter?" type="button" style="padding: 4px 10px; font-size: 12px; border: 1px solid #fecaca; border-radius: 6px; background: #fff; cursor: pointer; color: #dc2626;">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 24px; text-align: center; color: #9ca3af;">No waiting list filters defined.</td>
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
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">{{ $editingId ? 'Edit Filter' : 'New Filter' }}</h3>
                    <button wire:click="closeForm" type="button" style="color: #9ca3af; font-size: 20px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Filter Label *</label>
                        <input wire:model="formLabel" type="text" placeholder="e.g. Mulch" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Service Group *</label>
                        <select wire:model="formServiceGroup" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; background: #fff;">
                            @foreach($groups as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input wire:model="formIsActive" type="checkbox" style="width: 18px; height: 18px; accent-color: #c9092f;" />
                        <span style="font-size: 14px; color: #374151;">Active (shown on the waiting list)</span>
                    </label>
                </div>
                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                    <button wire:click="closeForm" type="button" style="padding: 9px 18px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;">Cancel</button>
                    <button wire:click="saveFilter" type="button" style="padding: 9px 18px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">{{ $editingId ? 'Update' : 'Create' }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
