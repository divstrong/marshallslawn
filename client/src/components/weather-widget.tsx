import { useEffect, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Card } from '@/components/ui';
import { AppColors, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { fetchCurrentWeather, weatherDisplay, type WeatherNow } from '@/lib/weather';

/**
 * Current-conditions weather for the crew's location. Renders nothing until
 * (and unless) weather is available, so it never blocks or clutters the view.
 */
export function WeatherWidget() {
  const { t } = useLanguage();
  const [weather, setWeather] = useState<WeatherNow | null>(null);

  useEffect(() => {
    let active = true;
    fetchCurrentWeather().then((result) => {
      if (active) {
        setWeather(result);
      }
    });
    return () => {
      active = false;
    };
  }, []);

  if (!weather) {
    return null;
  }

  const { icon, labelKey } = weatherDisplay(weather.code, weather.isDay);
  const condition = weather.city ? `${t(labelKey)} · ${weather.city}` : t(labelKey);

  return (
    <Card style={styles.card}>
      <Icon name={icon} size={38} color={AppColors.brand} />
      <View style={styles.main}>
        <Text style={styles.temp}>{weather.temperature}°</Text>
        <Text style={styles.condition} numberOfLines={1}>
          {condition}
        </Text>
      </View>
      <View style={styles.meta}>
        {weather.high != null && weather.low != null ? (
          <Text style={styles.metaText}>
            {t('weather.highLow', { high: weather.high, low: weather.low })}
          </Text>
        ) : null}
        <Text style={styles.metaText}>{t('weather.wind', { mph: weather.windSpeed })}</Text>
      </View>
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.three,
  },
  main: {
    flex: 1,
  },
  temp: {
    fontSize: 26,
    fontWeight: '800',
    color: AppColors.text,
    fontVariant: ['tabular-nums'],
  },
  condition: {
    fontSize: 13,
    color: AppColors.textMuted,
    marginTop: 1,
  },
  meta: {
    alignItems: 'flex-end',
    gap: 2,
  },
  metaText: {
    fontSize: 12,
    color: AppColors.textFaint,
    fontVariant: ['tabular-nums'],
  },
});
