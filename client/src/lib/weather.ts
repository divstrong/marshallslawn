/**
 * Current-conditions weather for the crew's location. Uses Open-Meteo
 * (free, no API key) and the device's foreground location. Best-effort:
 * every failure path resolves to null so the UI can simply hide the widget.
 */

import * as Location from 'expo-location';
import { Platform } from 'react-native';

import type { IconName } from '@/components/icon';

export interface WeatherNow {
  temperature: number; // °F
  high: number | null; // today's max °F
  low: number | null; // today's min °F
  windSpeed: number; // mph
  code: number; // WMO weather code
  isDay: boolean;
  city: string | null;
}

const isWeb = Platform.OS === 'web';

export async function fetchCurrentWeather(): Promise<WeatherNow | null> {
  if (isWeb) {
    return null;
  }

  try {
    let permission = await Location.getForegroundPermissionsAsync();
    if (!permission.granted && permission.canAskAgain) {
      permission = await Location.requestForegroundPermissionsAsync();
    }
    if (!permission.granted) {
      return null;
    }

    const position = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Low });
    const { latitude, longitude } = position.coords;

    const params = new URLSearchParams({
      latitude: String(latitude),
      longitude: String(longitude),
      current: 'temperature_2m,is_day,weather_code,wind_speed_10m',
      daily: 'temperature_2m_max,temperature_2m_min',
      temperature_unit: 'fahrenheit',
      wind_speed_unit: 'mph',
      timezone: 'auto',
      forecast_days: '1',
    });

    const response = await fetch(`https://api.open-meteo.com/v1/forecast?${params.toString()}`);
    if (!response.ok) {
      return null;
    }

    const data = await response.json();
    const current = data?.current ?? {};
    const daily = data?.daily ?? {};

    if (current.temperature_2m == null) {
      return null;
    }

    let city: string | null = null;
    try {
      const places = await Location.reverseGeocodeAsync({ latitude, longitude });
      city = places[0]?.city ?? places[0]?.subregion ?? null;
    } catch {
      // City is a nicety — ignore reverse-geocode failures.
    }

    return {
      temperature: Math.round(current.temperature_2m),
      high: daily.temperature_2m_max?.[0] != null ? Math.round(daily.temperature_2m_max[0]) : null,
      low: daily.temperature_2m_min?.[0] != null ? Math.round(daily.temperature_2m_min[0]) : null,
      windSpeed: Math.round(current.wind_speed_10m ?? 0),
      code: Number(current.weather_code ?? 0),
      isDay: current.is_day === 1 || current.is_day === true,
      city,
    };
  } catch {
    return null;
  }
}

export interface WeatherDisplay {
  icon: IconName;
  labelKey: string;
}

/** Map a WMO weather code to an Ionicons glyph + translation key. */
export function weatherDisplay(code: number, isDay: boolean): WeatherDisplay {
  if (code === 0) {
    return { icon: isDay ? 'sunny' : 'moon', labelKey: 'weather.clear' };
  }
  if (code === 1 || code === 2) {
    return { icon: isDay ? 'partly-sunny' : 'cloudy-night', labelKey: 'weather.partlyCloudy' };
  }
  if (code === 3) {
    return { icon: 'cloudy', labelKey: 'weather.cloudy' };
  }
  if (code === 45 || code === 48) {
    return { icon: 'cloudy', labelKey: 'weather.fog' };
  }
  if (code >= 51 && code <= 67) {
    return { icon: 'rainy', labelKey: 'weather.rain' };
  }
  if (code >= 71 && code <= 77) {
    return { icon: 'snow', labelKey: 'weather.snow' };
  }
  if (code >= 80 && code <= 82) {
    return { icon: 'rainy', labelKey: 'weather.showers' };
  }
  if (code === 85 || code === 86) {
    return { icon: 'snow', labelKey: 'weather.snow' };
  }
  if (code >= 95) {
    return { icon: 'thunderstorm', labelKey: 'weather.thunder' };
  }
  return { icon: 'partly-sunny', labelKey: 'weather.clear' };
}
