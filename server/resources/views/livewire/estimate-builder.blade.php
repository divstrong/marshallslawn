<div class="estimate-builder">
    <style>
        .dark .estimate-builder [style*="background: #fff"],
        .dark .estimate-builder [style*="background:#fff"] {
            background: #1f2937 !important;
        }
        .dark .estimate-builder [style*="background: #f9fafb"] {
            background: #111827 !important;
        }
        .dark .estimate-builder [style*="background: #f0fdf4"] {
            background: rgba(22, 101, 52, 0.15) !important;
        }
        .dark .estimate-builder [style*="color: #111827"],
        .dark .estimate-builder [style*="color:#111827"] {
            color: #f9fafb !important;
        }
        .dark .estimate-builder [style*="color: #374151"] {
            color: #d1d5db !important;
        }
        .dark .estimate-builder [style*="color: #6b7280"],
        .dark .estimate-builder [style*="color: #9ca3af"] {
            color: #9ca3af !important;
        }
        .dark .estimate-builder [style*="border: 1px solid #e5e7eb"],
        .dark .estimate-builder [style*="border: 1px solid #d1d5db"],
        .dark .estimate-builder [style*="border: 1px solid #f3f4f6"],
        .dark .estimate-builder [style*="border-bottom: 1px solid #e5e7eb"],
        .dark .estimate-builder [style*="border-bottom: 1px solid #f3f4f6"],
        .dark .estimate-builder [style*="border-top: 1px solid #e5e7eb"] {
            border-color: #374151 !important;
        }
        .dark .estimate-builder [style*="border-top: 2px solid #111827"] {
            border-top-color: #f9fafb !important;
        }
        .dark .estimate-builder input,
        .dark .estimate-builder select,
        .dark .estimate-builder textarea {
            color: #f9fafb !important;
            background: #111827 !important;
            border-color: #374151 !important;
        }
        .dark .estimate-builder input::placeholder,
        .dark .estimate-builder textarea::placeholder {
            color: #6b7280 !important;
        }
        .dark .estimate-builder select option {
            background: #1f2937;
            color: #f9fafb;
        }
        .dark .estimate-builder [style*="background: rgba(0,0,0,0.5)"] {
            background: rgba(0, 0, 0, 0.75) !important;
        }
        .dark .estimate-builder thead tr[style*="background: #f9fafb"] {
            background: #111827 !important;
        }
    </style>

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

            {{-- Customer selection --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Customer</label>
                @if($this->selectedCustomer)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f9fafb; border-radius: 8px;">
                        <div>
                            <p style="font-size: 15px; font-weight: 600; color: #111827;">{{ $this->selectedCustomer->first_name }} {{ $this->selectedCustomer->last_name }}</p>
                            @if($this->selectedCustomer->company_name)
                                <p style="font-size: 13px; color: #6b7280;">{{ $this->selectedCustomer->company_name }}</p>
                            @endif
                            @if($this->selectedCustomer->email)
                                <p style="font-size: 13px; color: #6b7280;">{{ $this->selectedCustomer->email }}</p>
                            @endif
                        </div>
                        <button wire:click="clearCustomer" type="button" style="color: #9ca3af; font-size: 18px; border: none; background: none; cursor: pointer;">&times;</button>
                    </div>

                    {{-- Property select --}}
                    @if($this->customerProperties->count() > 0)
                        <div style="margin-top: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Property</label>
                            <select wire:model.live="propertyId" style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff;">
                                <option value="">-- Select property --</option>
                                @foreach($this->customerProperties as $prop)
                                    <option value="{{ $prop->id }}">{{ $prop->address }}, {{ $prop->city }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Service Area --}}
                    <div style="margin-top: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Service Area Size (Ft&sup2;)</label>
                        <input
                            wire:model.live.debounce.500ms="squareFootage"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="e.g. 5000"
                            style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; box-sizing: border-box;"
                        />
                        @if($squareFootage && $propertyId)
                            <p style="font-size: 11px; color: #6b7280; margin-top: 4px;">Pre-filled from property. Edit to override for this estimate.</p>
                        @endif
                    </div>
                @else
                    <div style="display: flex; gap: 8px;">
                        <div style="position: relative; flex: 1;">
                            <input
                                wire:model.live.debounce.300ms="customerSearch"
                                wire:focus="$set('showCustomerDropdown', true)"
                                type="text"
                                placeholder="Search by name or company..."
                                style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"
                            />
                        @if($showCustomerDropdown && $this->customerResults->count() > 0)
                            <div style="position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin-top: 4px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 240px; overflow-y: auto;">
                                @foreach($this->customerResults as $cust)
                                    <button
                                        wire:click="selectCustomer({{ $cust->id }})"
                                        type="button"
                                        style="width: 100%; text-align: left; padding: 10px 14px; border: none; background: none; cursor: pointer; font-size: 14px; border-bottom: 1px solid #f3f4f6;"
                                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'"
                                    >
                                        <span style="font-weight: 500; color: #111827;">{{ $cust->first_name }} {{ $cust->last_name }}</span>
                                        @if($cust->company_name)
                                            <span style="color: #6b7280;"> — {{ $cust->company_name }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        </div>
                        <button
                            wire:click="openNewCustomerModal"
                            type="button"
                            title="Create new customer"
                            style="width: 42px; height: 42px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; font-size: 20px; font-weight: 600; color: #c9092f; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"
                        >+</button>
                    </div>
                @endif
            </div>

            {{-- Line Items --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <label style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Line Items</label>
                </div>

                {{-- Table header --}}
                @if(count($lineItems) > 0)
                    <div style="display: grid; grid-template-columns: 1fr 80px 100px 100px 40px; gap: 8px; padding: 0 0 8px; border-bottom: 1px solid #e5e7eb; margin-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase;">Description</span>
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: center;">Qty</span>
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: right;">Rate</span>
                        <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; text-align: right;">Total</span>
                        <span></span>
                    </div>
                @endif

                {{-- Line item rows (skip discounts — shown in totals) --}}
                @foreach($lineItems as $i => $item)
                    @if($item['is_discount'] ?? false) @continue @endif
                    <div wire:key="line-{{ $i }}" style="display: grid; grid-template-columns: 1fr 80px 100px 100px 40px; gap: 8px; align-items: center; padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                        <input
                            wire:model.blur="lineItems.{{ $i }}.description"
                            type="text"
                            placeholder="Description"
                            style="padding: 7px 10px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; width: 100%; box-sizing: border-box;"
                        />
                        <input
                            wire:model.blur="lineItems.{{ $i }}.quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            style="padding: 7px 6px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: center; width: 100%; box-sizing: border-box;"
                        />
                        <input
                            wire:model.blur="lineItems.{{ $i }}.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            style="padding: 7px 6px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: right; width: 100%; box-sizing: border-box;"
                        />
                        <span style="font-size: 14px; font-weight: 500; text-align: right; color: #111827; padding-right: 4px;">
                            ${{ $item['total'] }}
                        </span>
                        <button
                            wire:click="removeLine({{ $i }})"
                            type="button"
                            style="color: #dc2626; border: none; background: none; cursor: pointer; font-size: 16px; padding: 4px;"
                        >&times;</button>
                    </div>
                @endforeach

                {{-- Add line: Services & Packages --}}
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
                    <div style="display: flex; gap: 8px;">
                        {{-- Service search --}}
                        <div style="flex: 1; position: relative;">
                            <input
                                wire:model.live.debounce.300ms="serviceSearch"
                                wire:focus="$set('showServiceDropdown', true)"
                                type="text"
                                placeholder="Search services..."
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
                        {{-- Package search --}}
                        <div style="flex: 1; position: relative;">
                            <input
                                wire:model.live.debounce.300ms="packageSearch"
                                wire:focus="$set('showPackageDropdown', true)"
                                type="text"
                                placeholder="Search packages..."
                                style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"
                            />
                            @if($showPackageDropdown && $this->packageResults->count() > 0)
                                <div style="position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin-top: 4px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto;">
                                    @foreach($this->packageResults as $pkg)
                                        <button
                                            wire:click="addPackage({{ $pkg->id }})"
                                            type="button"
                                            style="width: 100%; text-align: left; padding: 10px 14px; border: none; background: none; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between;"
                                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='none'"
                                        >
                                            <div>
                                                <span style="font-weight: 500; color: #111827;">{{ $pkg->name }}</span>
                                                <span style="font-size: 11px; color: #9ca3af; margin-left: 4px;">({{ $pkg->services_count ?? $pkg->services()->count() }} services)</span>
                                            </div>
                                            <span style="color: #6b7280; font-weight: 500;">${{ number_format($pkg->price, 2) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button
                            wire:click="addCustomLine"
                            type="button"
                            style="padding: 8px 14px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151; white-space: nowrap;"
                        >+ Custom Line</button>
                        <button
                            wire:click="openDiscountModal"
                            type="button"
                            style="padding: 8px 14px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #c9092f; white-space: nowrap;"
                        >+ Discount</button>
                        <button
                            wire:click="openPricingCalc"
                            type="button"
                            style="padding: 8px 14px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #166534; white-space: nowrap;"
                        >Pricing Calculator</button>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Notes</label>
                <textarea
                    wire:model.blur="notes"
                    rows="3"
                    placeholder="Internal notes or terms..."
                    style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; box-sizing: border-box;"
                ></textarea>
            </div>
        </div>

        {{-- RIGHT: Summary sidebar --}}
        <div style="display: flex; flex-direction: column; gap: 16px;">
            {{-- Status & Date --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Status</label>
                <select wire:model.live="status" style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; margin-bottom: 12px;">
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="accepted">Accepted</option>
                    <option value="declined">Declined</option>
                    <option value="expired">Expired</option>
                </select>

                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Valid Until</label>
                <input
                    wire:model.blur="validUntil"
                    type="date"
                    style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;"
                />

                @if($estimate && $estimate->estimate_number)
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                        <span style="font-size: 12px; color: #6b7280;">Estimate #</span>
                        <span style="font-size: 14px; font-weight: 600; color: #111827; margin-left: 4px;">{{ $estimate->estimate_number }}</span>
                    </div>
                @endif
            </div>

            {{-- Totals --}}
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 14px; color: #6b7280;">Subtotal</span>
                    <span style="font-size: 14px; font-weight: 500; color: #111827;">${{ $subtotal }}</span>
                </div>

                {{-- Discount lines --}}
                @foreach($lineItems as $i => $item)
                    @if($item['is_discount'] ?? false)
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding: 6px 0; border-bottom: 1px dashed #fecaca;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 13px; color: #dc2626;">{{ $item['description'] }}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-size: 14px; font-weight: 500; color: #dc2626;">${{ $item['total'] }}</span>
                                <button
                                    wire:click="removeDiscount({{ $i }})"
                                    type="button"
                                    style="color: #9ca3af; border: none; background: none; cursor: pointer; font-size: 14px; padding: 0 2px;"
                                    title="Remove discount"
                                >&times;</button>
                            </div>
                        </div>
                    @endif
                @endforeach

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 14px; color: #6b7280;">Tax</span>
                    <input
                        wire:model.blur="tax"
                        type="number"
                        step="0.01"
                        min="0"
                        style="width: 100px; padding: 6px 8px; font-size: 14px; border: 1px solid #e5e7eb; border-radius: 6px; text-align: right; box-sizing: border-box;"
                    />
                </div>
                <div style="display: flex; justify-content: space-between; padding-top: 12px; border-top: 2px solid #111827;">
                    <span style="font-size: 16px; font-weight: 700; color: #111827;">Total</span>
                    <span style="font-size: 20px; font-weight: 700; color: #c9092f;">${{ $total }}</span>
                </div>
            </div>

            {{-- Actions --}}
            <div style="display: flex; flex-direction: column; gap: 8px; padding: 0 16px;">
                <button
                    wire:click="save"
                    type="button"
                    style="width: 100%; padding: 10px 12px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 10px; cursor: pointer;"
                >
                    {{ $isNew ? 'Create Estimate' : 'Save Changes' }}
                </button>
                <button
                    wire:click="openShareModal"
                    type="button"
                    style="width: 100%; padding: 10px 12px; font-size: 14px; font-weight: 600; color: #c9092f; background: #fff; border: 2px solid #c9092f; border-radius: 10px; cursor: pointer;"
                >
                    Share Estimate
                </button>
                @if($estimate && $estimate->share_token)
                    <a
                        href="{{ $estimate->getPublicUrl() }}"
                        target="_blank"
                        style="display: block; width: 100%; padding: 10px 12px; font-size: 14px; font-weight: 600; color: #374151; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 10px; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box;"
                    >
                        View Estimate
                    </a>
                @endif
            </div>

            {{-- Public link (if saved) --}}
            @if($estimate && $estimate->share_token)
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px;">
                    <label style="display: block; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 6px;">Public Link</label>
                    <input
                        type="text"
                        value="{{ $estimate->getPublicUrl() }}"
                        readonly
                        onclick="this.select(); document.execCommand('copy');"
                        style="width: 100%; padding: 8px 10px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; color: #374151; box-sizing: border-box; cursor: pointer;"
                    />
                    <p style="font-size: 11px; color: #9ca3af; margin-top: 4px;">Click to copy</p>
                </div>
            @endif
        </div>
    </div>

    {{-- New Customer Modal --}}
    @if($showNewCustomerModal)
        <div
            wire:click.self="closeNewCustomerModal"
            style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);"
        >
            <div style="width: 100%; max-width: 440px; margin: 0 16px; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;" @keydown.escape.window="$wire.closeNewCustomerModal()">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">New Customer</h3>
                    <button wire:click="closeNewCustomerModal" type="button" style="color: #9ca3af; font-size: 20px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">First Name *</label>
                            <input wire:model="newCustFirstName" type="text" placeholder="First" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Last Name *</label>
                            <input wire:model="newCustLastName" type="text" placeholder="Last" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Email</label>
                        <input wire:model="newCustEmail" type="email" placeholder="email@example.com" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Phone</label>
                        <input wire:model="newCustPhone" type="text" placeholder="(555) 555-5555" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Company</label>
                        <input wire:model="newCustCompany" type="text" placeholder="Company name (optional)" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                </div>
                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                    <button wire:click="closeNewCustomerModal" type="button" style="padding: 9px 18px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;">Cancel</button>
                    <button wire:click="createAndSelectCustomer" type="button" style="padding: 9px 18px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">Create & Select</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pricing Calculator Modal --}}
    @if($showPricingCalc)
        <div
            wire:click.self="closePricingCalc"
            style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);"
        >
            <div style="width: 100%; max-width: 780px; margin: 0 16px; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; max-height: 85vh; display: flex; flex-direction: column;" @keydown.escape.window="$wire.closePricingCalc()">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">Pricing Calculator</h3>
                        <p style="font-size: 12px; color: #6b7280; margin: 2px 0 0;">
                            @if($selectedLotSize)
                                Lot: {{ config('rate-matrix.lot_size_options')[$selectedLotSize] ?? $selectedLotSize }}
                                — Rate from matrix applied
                            @else
                                No lot size set — using default service prices
                            @endif
                        </p>
                    </div>
                    <button wire:click="closePricingCalc" type="button" style="color: #9ca3af; font-size: 20px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>

                <div style="flex: 1; overflow-y: auto; padding: 0;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #f9fafb; position: sticky; top: 0;">
                                <th style="text-align: left; padding: 10px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase;">Service</th>
                                <th style="text-align: right; padding: 10px 12px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; width: 90px;">Rate</th>
                                <th style="text-align: center; padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; width: 70px;">Qty</th>
                                <th style="text-align: center; padding: 10px 8px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; width: 70px;">Visits</th>
                                <th style="text-align: right; padding: 10px 16px; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; width: 100px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pricingRows as $i => $row)
                                <tr style="border-bottom: 1px solid #f3f4f6; {{ (float) $row['total'] > 0 ? 'background: #f0fdf4;' : '' }}">
                                    <td style="padding: 8px 16px; color: #111827;">{{ $row['name'] }}</td>
                                    <td style="padding: 8px 12px; text-align: right;">
                                        <input
                                            wire:model.blur="pricingRows.{{ $i }}.rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            style="width: 80px; padding: 5px 6px; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 4px; text-align: right; box-sizing: border-box;"
                                        />
                                    </td>
                                    <td style="padding: 8px 8px; text-align: center;">
                                        <input
                                            wire:model.live.debounce.300ms="pricingRows.{{ $i }}.qty"
                                            type="number"
                                            step="1"
                                            min="0"
                                            placeholder="0"
                                            style="width: 60px; padding: 5px 4px; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 4px; text-align: center; box-sizing: border-box;"
                                        />
                                    </td>
                                    <td style="padding: 8px 8px; text-align: center;">
                                        <input
                                            wire:model.live.debounce.300ms="pricingRows.{{ $i }}.visits"
                                            type="number"
                                            step="1"
                                            min="0"
                                            placeholder="0"
                                            style="width: 60px; padding: 5px 4px; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 4px; text-align: center; box-sizing: border-box;"
                                        />
                                    </td>
                                    <td style="padding: 8px 16px; text-align: right; font-weight: {{ (float) $row['total'] > 0 ? '600' : '400' }}; color: {{ (float) $row['total'] > 0 ? '#166534' : '#9ca3af' }};">
                                        ${{ $row['total'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="padding: 14px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; background: #f9fafb;">
                    <div>
                        @php
                            $calcTotal = 0;
                            $calcCount = 0;
                            foreach ($pricingRows as $r) {
                                if ((float) $r['total'] > 0) { $calcTotal += (float) $r['total']; $calcCount++; }
                            }
                        @endphp
                        <span style="font-size: 14px; font-weight: 600; color: #111827;">
                            {{ $calcCount }} service{{ $calcCount !== 1 ? 's' : '' }} — ${{ number_format($calcTotal, 2) }}
                        </span>
                        <span style="font-size: 12px; color: #6b7280; margin-left: 4px;">(Qty &times; Rate &times; Visits)</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button wire:click="closePricingCalc" type="button" style="padding: 9px 18px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;">Cancel</button>
                        <button wire:click="addPricingRowsAsLines" type="button" style="padding: 9px 18px; font-size: 14px; font-weight: 600; color: #fff; background: #166534; border: none; border-radius: 8px; cursor: pointer;">Add to Estimate</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Discount Modal --}}
    @if($showDiscountModal)
        <div
            wire:click.self="closeDiscountModal"
            style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);"
        >
            <div style="width: 100%; max-width: 380px; margin: 0 16px; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;" @keydown.escape.window="$wire.closeDiscountModal()">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #111827;">Add Discount</h3>
                    <button wire:click="closeDiscountModal" type="button" style="color: #9ca3af; font-size: 20px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>
                <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    {{-- Discount type toggle --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 8px;">Discount Type</label>
                        <div style="display: flex; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden;">
                            <button
                                wire:click="$set('discountType', 'percent')"
                                type="button"
                                style="flex: 1; padding: 10px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.15s; {{ $discountType === 'percent' ? 'background: #c9092f; color: #fff;' : 'background: #fff; color: #374151;' }}"
                            >% Percentage</button>
                            <button
                                wire:click="$set('discountType', 'dollar')"
                                type="button"
                                style="flex: 1; padding: 10px; font-size: 14px; font-weight: 500; border: none; border-left: 1px solid #d1d5db; cursor: pointer; transition: all 0.15s; {{ $discountType === 'dollar' ? 'background: #c9092f; color: #fff;' : 'background: #fff; color: #374151;' }}"
                            >$ Fixed Amount</button>
                        </div>
                    </div>

                    {{-- Amount input --}}
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                            {{ $discountType === 'percent' ? 'Percentage Off' : 'Dollar Amount' }}
                        </label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: #6b7280;">
                                {{ $discountType === 'percent' ? '%' : '$' }}
                            </span>
                            <input
                                wire:model="discountAmount"
                                type="number"
                                step="0.01"
                                min="0"
                                {{ $discountType === 'percent' ? 'max=100' : '' }}
                                placeholder="{{ $discountType === 'percent' ? 'e.g. 10' : 'e.g. 50.00' }}"
                                style="width: 100%; padding: 10px 12px 10px 32px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;"
                                autofocus
                            />
                        </div>
                        @if($discountType === 'percent' && $discountAmount)
                            @php
                                $previewSub = 0;
                                foreach ($lineItems as $li) {
                                    if (!($li['is_discount'] ?? false)) $previewSub += (float) ($li['total'] ?? 0);
                                }
                                $previewDiscount = round($previewSub * ((float) $discountAmount / 100), 2);
                            @endphp
                            <p style="font-size: 12px; color: #6b7280; margin-top: 6px;">
                                {{ $discountAmount }}% of ${{ number_format($previewSub, 2) }} = <strong style="color: #dc2626;">-${{ number_format($previewDiscount, 2) }}</strong>
                            </p>
                        @endif
                    </div>
                </div>
                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                    <button wire:click="closeDiscountModal" type="button" style="padding: 9px 18px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;">Cancel</button>
                    <button wire:click="applyDiscount" type="button" style="padding: 9px 18px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">Apply Discount</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Share Modal --}}
    @if($showShareModal)
        <div
            wire:click.self="closeShareModal"
            style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);"
        >
            <div style="width: 100%; max-width: 480px; margin: 0 16px; background: #fff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827;">Share Estimate</h3>
                    <button wire:click="closeShareModal" type="button" style="color: #9ca3af; font-size: 22px; border: none; background: none; cursor: pointer;">&times;</button>
                </div>

                @if($shareSent)
                    <div style="padding: 40px 24px; text-align: center;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #d1fae5; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px;">&#10003;</div>
                        <p style="font-size: 16px; font-weight: 600; color: #111827;">Estimate Sent!</p>
                        <p style="font-size: 14px; color: #6b7280; margin-top: 4px;">Sent to {{ $shareEmail }}</p>
                        <button
                            wire:click="closeShareModal"
                            type="button"
                            style="margin-top: 20px; padding: 10px 24px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;"
                        >Done</button>
                    </div>
                @else
                    <div style="padding: 20px 24px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Send to</label>
                        <input
                            wire:model="shareEmail"
                            type="email"
                            placeholder="customer@email.com"
                            style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; margin-bottom: 16px; box-sizing: border-box;"
                        />

                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Message</label>
                        <textarea
                            wire:model="shareMessage"
                            rows="5"
                            style="width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; box-sizing: border-box;"
                        ></textarea>

                        @if($estimate && $estimate->share_token)
                            <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                                Link included: <strong>{{ $estimate->getPublicUrl() }}</strong>
                            </p>
                        @endif
                    </div>
                    <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
                        <button
                            wire:click="closeShareModal"
                            type="button"
                            style="padding: 10px 20px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151;"
                        >Cancel</button>
                        <button
                            wire:click="sendEstimate"
                            type="button"
                            style="padding: 10px 20px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;"
                        >Send Estimate</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
