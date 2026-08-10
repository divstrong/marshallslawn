@include('filament.resources.customer.overview-styles')

@php $properties = $this->snapshot()['properties']; @endphp

<div class="cov-list">
    @forelse ($properties as $property)
        @php $imageUrl = $property->primaryImageUrl(); @endphp
        <div class="cov-row">
            {{-- Reference photo on the primary property only — the others stay compact. --}}
            @if ($property->is_primary)
                <img
                    src="{{ $imageUrl ?: \App\Models\Property::placeholderImageUrl() }}"
                    alt="{{ $imageUrl ? 'Photo of ' . $property->address : 'No photo on file — add one on the property' }}"
                    title="{{ $imageUrl ? $property->address : 'No photo on file yet' }}"
                    class="cov-thumb {{ $imageUrl ? '' : 'is-empty' }}"
                >
            @endif
            <div class="cov-row-main">
                <div class="cov-row-title">{{ $property->address ?: 'No street address' }}</div>
                <div class="cov-row-sub">
                    {{ collect([$property->city, $property->state, $property->zip])->filter()->join(', ') ?: 'No city on file' }}
                </div>
                @if ($property->lawn_size || $property->lot_size)
                    <div class="cov-row-sub">
                        {{ collect([
                            $property->lawn_size ? $property->lawn_size . ' lawn' : null,
                            $property->lot_size ? $property->lot_size . ' lot' : null,
                        ])->filter()->join(' · ') }}
                    </div>
                @endif
            </div>
            <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-end;">
                @if ($property->is_primary)
                    <span class="cov-pill is-primary">Primary</span>
                @endif
                @if (! $property->hasCoordinates())
                    {{-- Without coordinates the stop can't be placed on the dispatch map. --}}
                    <span class="cov-pill is-warn" title="Not geocoded — this property won't appear on the dispatch map">No pin</span>
                @endif
            </div>
        </div>
    @empty
        <p class="cov-empty">No properties on file yet.</p>
    @endforelse
</div>
