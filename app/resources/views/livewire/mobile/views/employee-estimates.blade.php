<div class="{{ $mode === 'list' ? 'p-4 space-y-4' : '' }}">
    @if($mode === 'list')
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-bold text-gray-800">My Estimates</h1>
            <button wire:click="startCreate" class="inline-flex items-center gap-1.5 bg-brand-500 text-white px-3 py-2 rounded-lg text-sm font-semibold hover:bg-brand-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Estimate
            </button>
        </div>

        <!-- Status filter -->
        <div class="bg-white rounded-xl shadow-sm p-1 flex gap-1 overflow-x-auto">
            @foreach(['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'declined' => 'Declined'] as $key => $label)
                <button wire:click="$set('statusFilter', '{{ $key }}')" class="flex-1 py-2 px-2 rounded-lg text-xs font-medium transition-all whitespace-nowrap {{ $statusFilter === $key ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if($this->estimates->isEmpty())
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                @include('livewire.mobile.partials.nav-icon', ['icon' => 'document-text', 'class' => 'w-16 h-16 text-gray-300 mx-auto'])
                <p class="text-gray-400 mt-3">
                    @if($statusFilter === 'all')
                        You haven't created any estimates yet.
                    @else
                        No {{ $statusFilter }} estimates.
                    @endif
                </p>
                <button wire:click="startCreate" class="mt-4 inline-flex items-center gap-1.5 bg-brand-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-brand-600 transition-colors">
                    Create Your First Estimate
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->estimates as $estimate)
                    @php
                        $customer = $estimate->customer;
                        $customerName = $customer
                            ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                            : 'Unknown customer';
                    @endphp
                    <button wire:click="viewEstimate({{ $estimate->id }})" class="w-full bg-white rounded-xl shadow-sm p-4 text-left hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800">{{ $estimate->estimate_number }}</p>
                                <p class="text-xs text-gray-600 mt-0.5 truncate">{{ $customerName }}</p>
                                @if($customer?->company_name)
                                    <p class="text-xs text-gray-400 truncate">{{ $customer->company_name }}</p>
                                @endif
                            </div>
                            <span class="ml-2 shrink-0 px-2 py-1 text-xs font-medium rounded-full
                                {{ $estimate->status === 'accepted' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $estimate->status === 'declined' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $estimate->status === 'sent' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $estimate->status === 'draft' ? 'bg-gray-100 text-gray-600' : '' }}
                                {{ $estimate->status === 'expired' ? 'bg-amber-100 text-amber-700' : '' }}">
                                {{ ucfirst($estimate->status) }}
                            </span>
                        </div>
                        @if($estimate->property)
                            <p class="text-xs text-gray-500 truncate">{{ $estimate->property->address }}</p>
                        @endif
                        <div class="flex items-end justify-between mt-2">
                            <div class="text-xs text-gray-400">
                                @if($estimate->valid_until)
                                    Valid until {{ $estimate->valid_until->format('M j, Y') }}
                                @else
                                    Created {{ $estimate->created_at->diffForHumans() }}
                                @endif
                            </div>
                            <div class="text-base font-bold text-gray-800">
                                ${{ number_format((float) $estimate->total, 2) }}
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <!-- Create / Edit mode: embed the existing EstimateBuilder -->
        <div class="bg-white border-b border-gray-200 px-4 py-2 flex items-center justify-between sticky top-0 z-10">
            <button wire:click="backToList" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to estimates
            </button>
            <span class="text-xs text-gray-500">
                {{ $mode === 'create' ? 'New Estimate' : 'Edit Estimate' }}
                @if($this->viewingEstimate)
                    · {{ $this->viewingEstimate->estimate_number }}
                @endif
            </span>
        </div>

        @if($mode === 'edit' && $this->viewingEstimate)
            <livewire:estimate-builder :estimate="$this->viewingEstimate" wire:key="estimate-builder-{{ $viewingEstimateId }}" />
        @else
            <livewire:estimate-builder wire:key="estimate-builder-new" />
        @endif
    @endif
</div>
