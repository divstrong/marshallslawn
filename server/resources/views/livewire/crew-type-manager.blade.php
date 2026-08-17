<div>
    @if(session('crew-type-error'))
        <div style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('crew-type-error') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <p style="font-size: 13px; color: #6b7280;">Types a crew can be assigned. These drive the crew filter buttons on the Dispatch board, in the order listed here.</p>
        <button wire:click="openCreate" type="button" style="padding: 8px 16px; font-size: 13px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            + New Type
        </button>
    </div>

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Label</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Key</th>
                    <th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Active</th>
                    <th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Crews</th>
                    <th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Order</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $i => $type)
                    <tr style="border-top: 1px solid #f3f4f6;">
                        <td style="padding: 12px 16px; font-weight: 600; color: #111827;">{{ $type['label'] }}</td>
                        <td style="padding: 12px 16px; color: #6b7280; font-family: monospace; font-size: 12px;">{{ $type['name'] }}</td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @if($type['is_active'])
                                <span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #065f46; background: #d1fae5;">Active</span>
                            @else
                                <span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; color: #6b7280; background: #f3f4f6;">Hidden</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; text-align: center; color: #6b7280;">{{ $type['crews_count'] }}</td>
                        <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                            <button wire:click="move({{ $type['id'] }}, 'up')" type="button" @disabled($i === 0) style="padding: 2px 8px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; color: #374151; opacity: {{ $i === 0 ? '0.4' : '1' }};">&uarr;</button>
                            <button wire:click="move({{ $type['id'] }}, 'down')" type="button" @disabled($i === count($types) - 1) style="padding: 2px 8px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; color: #374151; opacity: {{ $i === count($types) - 1 ? '0.4' : '1' }};">&darr;</button>
                        </td>
                        <td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
                            <button wire:click="openEdit({{ $type['id'] }})" type="button" style="padding: 4px 10px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; color: #374151; margin-right: 4px;">Edit</button>
                            @if($type['crews_count'] === 0)
                                <button wire:click="deleteType({{ $type['id'] }})" wire:confirm="Delete this crew type?" type="button" style="padding: 4px 10px; font-size: 12px; border: 1px solid #fecaca; border-radius: 6px; background: #fff; cursor: pointer; color: #dc2626;">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: #9ca3af;">No crew types defined.</td>
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
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">{{ $editingId ? 'Edit Crew Type' : 'New Crew Type' }}</h3>
                    <button wire:click="closeForm" type="button" style="color: #9ca3af; font-size: 20px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Type Label *</label>
                        <input wire:model="formLabel" type="text" placeholder="e.g. Mulching" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                        @if($editingId)
                            <p style="font-size: 11px; color: #9ca3af; margin-top: 4px;">Renaming is safe — crews stay attached.</p>
                        @endif
                    </div>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; cursor: pointer;">
                        <input wire:model="formActive" type="checkbox" />
                        Active — show on crew forms and the Dispatch board
                    </label>
                </div>
                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                    <button wire:click="closeForm" type="button" style="padding: 9px 18px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;">Cancel</button>
                    <button wire:click="saveType" type="button" style="padding: 9px 18px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">{{ $editingId ? 'Update' : 'Create' }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
