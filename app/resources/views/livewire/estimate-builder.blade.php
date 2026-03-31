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

                {{-- Line item rows --}}
                @foreach($lineItems as $i => $item)
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

                {{-- Add line buttons --}}
                <div style="display: flex; gap: 8px; margin-top: 12px; position: relative;">
                    <div style="flex: 1; position: relative;">
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
                    <button
                        wire:click="addCustomLine"
                        type="button"
                        style="padding: 8px 14px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; cursor: pointer; color: #374151; white-space: nowrap;"
                    >+ Custom Line</button>
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
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button
                    wire:click="save"
                    type="button"
                    style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 10px; cursor: pointer;"
                >
                    {{ $isNew ? 'Create Estimate' : 'Save Changes' }}
                </button>
                <button
                    wire:click="openShareModal"
                    type="button"
                    style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600; color: #c9092f; background: #fff; border: 2px solid #c9092f; border-radius: 10px; cursor: pointer;"
                >
                    Share Estimate
                </button>
                @if($estimate && $estimate->share_token)
                    <a
                        href="{{ $estimate->getPublicUrl() }}"
                        target="_blank"
                        style="display: block; width: 100%; padding: 12px; font-size: 14px; font-weight: 600; color: #374151; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 10px; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box;"
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
