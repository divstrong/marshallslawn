<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Notification;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerHomeView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public ?array $weather = null;

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
        $this->fetchWeather();
    }

    public function getCustomerProperty(): ?Customer
    {
        $customerId = session('mobile_app_user_id');
        return $customerId ? Customer::find($customerId) : null;
    }

    public function getUpcomingJobsProperty()
    {
        if (!$this->customer) return collect();

        return Job::where('customer_id', $this->customer->id)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('scheduled_date', '>=', now()->startOfDay())
            ->orderBy('scheduled_date')
            ->with(['property', 'crew'])
            ->limit(5)
            ->get();
    }

    public function getNotificationsProperty()
    {
        if (!$this->customer) return collect();

        return Notification::where('notifiable_type', Customer::class)
            ->where('notifiable_id', $this->customer->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function fetchWeather()
    {
        $cacheKey = 'mobile_weather_' . session('mobile_app_user_id');
        $this->weather = cache()->remember($cacheKey, 1800, function () {
            try {
                // Default to a central location; in production would use GPS
                $lat = 39.0;
                $lon = -84.5;
                $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&temperature_unit=fahrenheit&wind_speed_unit=mph";
                $response = file_get_contents($url);
                if ($response) {
                    $data = json_decode($response, true);
                    $current = $data['current'] ?? [];
                    return [
                        'temp' => round($current['temperature_2m'] ?? 0),
                        'humidity' => $current['relative_humidity_2m'] ?? 0,
                        'wind' => round($current['wind_speed_10m'] ?? 0),
                        'condition' => $this->weatherCodeToText($current['weather_code'] ?? 0),
                    ];
                }
            } catch (\Exception $e) {
                // Silently fail
            }
            return null;
        });
    }

    private function weatherCodeToText(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear Sky',
            $code <= 3 => 'Partly Cloudy',
            $code <= 49 => 'Foggy',
            $code <= 59 => 'Drizzle',
            $code <= 69 => 'Rain',
            $code <= 79 => 'Snow',
            $code <= 82 => 'Rain Showers',
            $code <= 86 => 'Snow Showers',
            $code <= 99 => 'Thunderstorm',
            default => 'Unknown',
        };
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-home', [
            't' => $this->translations,
        ]);
    }
}
