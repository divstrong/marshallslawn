<div style="padding: 8px 0;">
    {{-- Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <p style="font-size: 14px; color: #6b7280;">
            {{ $this->members->count() }} {{ Str::plural('member', $this->members->count()) }} assigned
        </p>
        <button
            wire:click="openAddModal"
            type="button"
            style="background-color: #c9092f; color: #fff; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer;"
        >
            + Add Member
        </button>
    </div>

    {{-- Members List --}}
    @if($this->members->count() > 0)
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; font-size: 14px;">
            <thead>
                <tr style="background: #f9fafb; text-align: left;">
                    <th style="padding: 10px 16px; font-weight: 600; color: #374151; width: 50px;"></th>
                    <th style="padding: 10px 16px; font-weight: 600; color: #374151;">Name</th>
                    <th style="padding: 10px 16px; font-weight: 600; color: #374151;">Status</th>
                    <th style="padding: 10px 16px; font-weight: 600; color: #374151; text-align: right;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->members as $member)
                    <tr wire:key="member-{{ $member->id }}" style="border-top: 1px solid #e5e7eb;">
                        <td style="padding: 10px 16px;">
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: #fde4e8; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; color: #c9092f;">
                                {{ strtoupper(substr($member->employee->first_name ?? $member->employee->name, 0, 1)) }}{{ strtoupper(substr($member->employee->last_name ?? '', 0, 1)) }}
                            </div>
                        </td>
                        <td style="padding: 10px 16px; color: #111827; font-weight: 500;">
                            {{ $member->employee->first_name }} {{ $member->employee->last_name }}
                        </td>
                        <td style="padding: 10px 16px;">
                            <span style="display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 500; background: #d1fae5; color: #065f46;">
                                {{ ucfirst($member->employee->status ?? 'active') }}
                            </span>
                        </td>
                        <td style="padding: 10px 16px; text-align: right;">
                            <button
                                wire:click="removeMember({{ $member->id }})"
                                wire:confirm="Remove {{ $member->employee->first_name }} {{ $member->employee->last_name }} from this crew?"
                                type="button"
                                style="color: #dc2626; font-size: 13px; font-weight: 500; padding: 4px 12px; border-radius: 6px; border: 1px solid #fecaca; background: #fff; cursor: pointer;"
                            >
                                Remove
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 32px; text-align: center;">
            <p style="font-size: 14px; font-weight: 500; color: #111827;">No members assigned</p>
            <p style="font-size: 13px; color: #6b7280; margin-top: 4px;">Add employees to this crew to get started.</p>
        </div>
    @endif

    {{-- Add Member Modal --}}
    @if($showAddModal)
        <div
            wire:click.self="closeAddModal"
            style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);"
        >
            <div
                style="width: 100%; max-width: 420px; margin: 0 16px; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;"
                @keydown.escape.window="$wire.closeAddModal()"
            >
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">Add Crew Member</h3>
                    <button wire:click="closeAddModal" type="button" style="color: #9ca3af; font-size: 20px; line-height: 1; padding: 4px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>

                <div style="padding: 16px 20px;">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search employees..."
                        autofocus
                        style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 10px; outline: none; box-sizing: border-box;"
                    />
                </div>

                <div style="max-height: 280px; overflow-y: auto; padding: 0 12px 16px;">
                    @if(strlen($search) < 1)
                        <p style="padding: 24px; text-align: center; font-size: 14px; color: #6b7280;">
                            Type to search for employees...
                        </p>
                    @elseif($this->filteredEmployees->isEmpty())
                        <p style="padding: 24px; text-align: center; font-size: 14px; color: #6b7280;">
                            No matching employees found.
                        </p>
                    @else
                        @foreach($this->filteredEmployees as $employee)
                            <button
                                wire:click="addMember({{ $employee->id }})"
                                wire:key="emp-{{ $employee->id }}"
                                type="button"
                                style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; text-align: left; border: none; background: none; cursor: pointer; font-size: 14px;"
                                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'"
                            >
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px; color: #4b5563; flex-shrink: 0;">
                                    {{ strtoupper(substr($employee->first_name ?? $employee->name, 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}
                                </div>
                                <div style="flex: 1;">
                                    <p style="font-weight: 500; color: #111827;">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </p>
                                </div>
                                <span style="color: #c9092f; font-size: 18px; font-weight: 600;">+</span>
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
