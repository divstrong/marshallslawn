<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'address',
        'city',
        'state',
        'zip',
        'latitude',
        'longitude',
        'geocoded_at',
        'lot_size',
        'lawn_size',
        'square_footage',
        'notes',
        'is_primary',
        'primary_image_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'square_footage' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'geocoded_at' => 'datetime',
            'is_primary' => 'boolean',
        ];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Public URL for the property's reference photo, or null when none is set so
     * callers can fall back to the "needs a photo" placeholder.
     */
    public function primaryImageUrl(): ?string
    {
        if (blank($this->primary_image_path)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->primary_image_path);
    }

    /** The stand-in shown wherever a property has no photo on file yet. */
    public static function placeholderImageUrl(): string
    {
        return asset('img/property-placeholder.svg');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function chemicalLogs(): HasMany
    {
        return $this->hasMany(ChemicalLog::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PropertyMedia::class);
    }
}
