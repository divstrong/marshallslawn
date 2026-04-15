<div>
    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
        <div>
            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Select Role</label>
            <select wire:model.live="selectedRoleId" style="padding: 9px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; min-width: 220px;">
                <option value="">-- Choose role --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->label ?? $role->name }}</option>
                @endforeach
            </select>
        </div>
        @if($this->selectedRole?->is_admin)
            <div style="padding: 8px 16px; background: #d1fae5; color: #065f46; border-radius: 8px; font-size: 13px; font-weight: 500; margin-top: 18px;">
                Admin roles have full access — permissions below are ignored.
            </div>
        @endif
    </div>

    @if($selectedRoleId && !$this->selectedRole?->is_admin)
        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
            @php $currentGroup = null; @endphp
            @foreach($availableResources as $resource)
                @if($resource['group'] !== $currentGroup)
                    @php $currentGroup = $resource['group']; @endphp
                    <div style="padding: 10px 16px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                        {{ $currentGroup }}
                    </div>
                @endif
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-top: 1px solid #f3f4f6;">
                    <span style="font-size: 14px; color: #111827;">{{ $resource['label'] }}</span>
                    @php $enabled = $permissions[$resource['class']] ?? false; @endphp
                    <button
                        wire:click="togglePermission('{{ $resource['class'] }}')"
                        type="button"
                        style="position: relative; width: 44px; height: 24px; border-radius: 12px; border: none; cursor: pointer; transition: background 0.2s; {{ $enabled ? 'background: #059669;' : 'background: #d1d5db;' }}"
                    >
                        <span style="position: absolute; top: 2px; {{ $enabled ? 'left: 22px;' : 'left: 2px;' }} width: 20px; height: 20px; background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: left 0.2s;"></span>
                    </button>
                </div>
            @endforeach
        </div>
    @elseif(!$selectedRoleId)
        <p style="font-size: 13px; color: #9ca3af; text-align: center; padding: 40px 0;">Select a role to manage its resource permissions.</p>
    @endif
</div>
