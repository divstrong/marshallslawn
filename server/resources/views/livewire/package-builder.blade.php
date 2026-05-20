<div>
    {{-- Flash messages --}}
    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background: #fee2e2; color: #991b1b; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('error') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px;">
        {{-- LEFT: Main form --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">

            {{-- Package details --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Package Details</label>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Name *</label>
                        <input
                            wire:model.blur="name"
                            type="text"
                            placeholder="e.g. Full Lawn Care Package"
                            style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;"
                        />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Description</label>
                        <textarea
                            wire:model.blur="description"
                            rows="2"
                            placeholder="Brief description of what's included..."
                            style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; box-sizing: border-box;"
                        ></textarea>
                    </div>
                </div>
            </div>

            {{-- Services --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">Included Services</label>

                {{-- Table header --}}
                @if(count($packageServices) > 0)
                    <div style="display: grid; grid-template-columns: 1fr 80px 100px 40px; gap: 8px; padding: 0 0 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Service</span>
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: center;">Qty</span>
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: right;">Ind. Price</span>
                        <span></span>
                    </div>
                @endif

                {{-- Service rows --}}
                @foreach($packageServices as $i => $ps)
                    <div wire:key="pkg-svc-{{ $i }}" style="display: grid; grid-template-columns: 1fr 80px 100px 40px; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                        <span style="font-size: 14px; color: #111827;">{{ $ps['name'] }}</span>
                        <input
                            wire:model.blur="packageServices.{{ $i }}.quantity"
                            type="number"
                            min="1"
                            step="1"
                            style="padding: 7px 6px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: center; width: 100%; box-sizing: border-box;"
                        />
                        <span style="font-size: 13px; color: #6b7280; text-align: right;">
                            ${{ number_format((float) $ps['default_price'] * (int) ($ps['quantity'] ?? 1), 2) }}
                        </span>
                        <button
                            wire:click="removeService({{ $i }})"
                            type="button"
                            style="color: #dc2626; border: none; background: none; cursor: pointer; font-size: 16px; padding: 4px;"
                        >&times;</button>
                    </div>
                @endforeach

                @if(count($packageServices) === 0)
                    <p style="font-size: 13px; color: #9ca3af; text-align: center; padding: 16px 0;">No services added yet. Search below to add services.</p>
                @endif

                {{-- Search to add --}}
                <div style="margin-top: 12px; position: relative;">
                    <input
                        wire:model.live.debounce.300ms="serviceSearch"
                        wire:focus="$set('showServiceDropdown', true)"
                        type="text"
                        placeholder="Search services to add..."
                        style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"
                    />
                    @if($showServiceDropdown && $this->serviceResults->count() > 0)
                        <div style="position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin-top: 4px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto;">
                            @foreach($this->serviceResults as $svc)
                                <button
                                    wire:click="addService({{ $svc->id }})"
                                    type="button"
                                    style="width: 100%; text-align: left; padding: 10px 14px; border: none; background: none; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;"
                                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'"
                                >
                                    <span style="font-weight: 500; color: #111827;">{{ $svc->name }}</span>
                                    @if($svc->default_price)
                                        <span style="color: #6b7280;">${{ number_format($svc->default_price, 2) }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: Sidebar --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">
            {{-- Pricing --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Status</label>
                <div style="margin-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input
                            wire:model.live="isActive"
                            type="checkbox"
                            style="width: 18px; height: 18px; accent-color: #c9092f;"
                        />
                        <span style="font-size: 14px; color: #374151;">Active</span>
                    </label>
                </div>

                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Package Price</label>
                <div style="position: relative; margin-bottom: 16px;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: #6b7280;">$</span>
                    <input
                        wire:model.blur="price"
                        type="number"
                        step="0.01"
                        min="0"
                        style="width: 100%; padding: 10px 12px 10px 28px; font-size: 16px; font-weight: 600; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;"
                    />
                </div>

                {{-- Pricing comparison --}}
                @if(count($packageServices) > 0)
                    <div style="padding-top: 12px; border-top: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-size: 13px; color: #6b7280;">Individual Total</span>
                            <span style="font-size: 13px; color: #6b7280;">${{ $this->servicesSubtotal }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-size: 13px; color: #6b7280;">Package Price</span>
                            <span style="font-size: 14px; font-weight: 600; color: #111827;">${{ number_format((float) $price, 2) }}</span>
                        </div>
                        @if((float) $this->savings > 0)
                            <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed #bbf7d0;">
                                <span style="font-size: 13px; font-weight: 600; color: #166534;">Customer Saves</span>
                                <span style="font-size: 14px; font-weight: 700; color: #166534;">${{ $this->savings }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button
                    wire:click="save"
                    type="button"
                    style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 10px; cursor: pointer;"
                >
                    {{ $isNew ? 'Create Package' : 'Save Changes' }}
                </button>
                @if(!$isNew)
                    <a
                        href="{{ route('filament.admin.resources.packages.index') }}"
                        style="display: block; width: 100%; padding: 12px; font-size: 14px; font-weight: 600; color: #374151; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 10px; text-align: center; text-decoration: none; box-sizing: border-box;"
                    >
                        Back to Packages
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
