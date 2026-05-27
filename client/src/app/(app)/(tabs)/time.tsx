import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Button, Card, ErrorState, LoadingState, ScreenHeader, SectionLabel } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { api } from '@/lib/api';
import { formatDateLong, formatDuration, formatHoursMinutes, formatTime, minutesSince } from '@/lib/format';
import type { TimeLog } from '@/lib/types';

export default function TimeScreen() {
  const { t, language } = useLanguage();
  const [now, setNow] = useState(() => new Date());
  const [refreshing, setRefreshing] = useState(false);
  const [busy, setBusy] = useState(false);

  const { data, loading, error, reload } = useApiResource(() => api.timeLogs(), []);

  // Tick the live clock once a second.
  useEffect(() => {
    const timer = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  }, [reload]);

  const activeShift = data?.active_shift ?? null;
  const locale = language === 'es' ? 'es' : 'en-US';

  // Live total: completed shifts + the running shift's elapsed minutes.
  const minutesToday = useMemo(() => {
    if (!data) return 0;
    return data.today.reduce((total, log) => {
      if (log.is_active && log.clock_in) {
        return total + Math.max(0, minutesSince(log.clock_in) - log.break_minutes);
      }
      return total + log.duration_minutes;
    }, 0);
    // `now` keeps the running shift fresh.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [data, now]);

  const handleClock = useCallback(
    async (action: 'in' | 'out') => {
      setBusy(true);
      try {
        if (action === 'in') {
          await api.clockIn();
        } else {
          await api.clockOut();
        }
        await reload();
      } catch (e) {
        Alert.alert(t('time.title'), e instanceof Error ? e.message : t('common.somethingWrong'));
      } finally {
        setBusy(false);
      }
    },
    [reload, t],
  );

  return (
    <View style={styles.screen}>
      <ScreenHeader title={t('time.title')} subtitle={t('time.subtitle')} />

      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {/* Live clock */}
        <Card style={styles.clockCard}>
          <Text style={styles.clockTime}>
            {now.toLocaleTimeString(locale, {
              hour: 'numeric',
              minute: '2-digit',
              second: '2-digit',
            })}
          </Text>
          <Text style={styles.clockDate}>
            {now.toLocaleDateString(locale, {
              weekday: 'long',
              month: 'long',
              day: 'numeric',
            })}
          </Text>
        </Card>

        {/* Hours today */}
        <View style={styles.hoursCard}>
          <Text style={styles.hoursLabel}>{t('time.hoursToday')}</Text>
          <Text style={styles.hoursValue}>{formatHoursMinutes(minutesToday)}</Text>
        </View>

        {loading ? (
          <LoadingState label={t('time.loading')} />
        ) : error ? (
          <ErrorState message={error} onRetry={reload} />
        ) : (
          <>
            {/* Clock in / out */}
            <Card style={styles.shiftCard}>
              <View style={styles.shiftStatusRow}>
                <View
                  style={[
                    styles.dot,
                    { backgroundColor: activeShift ? AppColors.success : AppColors.textFaint },
                  ]}
                />
                <Text style={styles.shiftStatusText}>
                  {activeShift ? t('time.onClock') : t('time.notClocked')}
                </Text>
              </View>
              {activeShift ? (
                <Text style={styles.shiftMeta}>
                  {t('time.clockedInAt', { time: formatTime(activeShift.clock_in) })}
                </Text>
              ) : (
                <Text style={styles.shiftMeta}>{t('time.startHint')}</Text>
              )}
              {activeShift ? (
                <Button
                  label={t('time.clockOut')}
                  icon="stop-circle-outline"
                  variant="danger"
                  loading={busy}
                  onPress={() => handleClock('out')}
                />
              ) : (
                <Button
                  label={t('time.clockIn')}
                  icon="play-circle-outline"
                  variant="success"
                  loading={busy}
                  onPress={() => handleClock('in')}
                />
              )}
            </Card>

            {/* Today's shifts */}
            {data && data.today.length > 0 ? (
              <View style={styles.section}>
                <SectionLabel>{t('time.todaySection')}</SectionLabel>
                <Card style={styles.logList}>
                  {data.today.map((log, index) => (
                    <ShiftRow key={log.id} log={log} divider={index > 0} />
                  ))}
                </Card>
              </View>
            ) : null}

            {/* History */}
            {data && data.history.length > 0 ? (
              <View style={styles.section}>
                <SectionLabel>{t('time.recentShifts')}</SectionLabel>
                <Card style={styles.logList}>
                  {data.history.map((log, index) => (
                    <ShiftRow key={log.id} log={log} divider={index > 0} showDate />
                  ))}
                </Card>
              </View>
            ) : null}
          </>
        )}
      </ScrollView>
    </View>
  );
}

function ShiftRow({
  log,
  divider,
  showDate,
}: {
  log: TimeLog;
  divider: boolean;
  showDate?: boolean;
}) {
  const { t } = useLanguage();
  const minutes = log.is_active && log.clock_in ? minutesSince(log.clock_in) : log.duration_minutes;

  return (
    <View style={[styles.logRow, divider && styles.logRowDivider]}>
      <View style={styles.logInfo}>
        <Text style={styles.logPrimary}>
          {showDate ? `${formatDateLong(log.clock_in)} · ` : ''}
          {formatTime(log.clock_in)} – {log.clock_out ? formatTime(log.clock_out) : t('time.active')}
        </Text>
        {log.break_minutes > 0 ? (
          <Text style={styles.logSecondary}>{t('time.break', { n: log.break_minutes })}</Text>
        ) : null}
      </View>
      <Text style={[styles.logDuration, log.is_active && { color: AppColors.success }]}>
        {formatDuration(minutes)}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  body: {
    padding: Spacing.four,
    gap: Spacing.three,
    flexGrow: 1,
  },
  clockCard: {
    alignItems: 'center',
    paddingVertical: Spacing.five,
  },
  clockTime: {
    fontSize: 40,
    fontWeight: '800',
    color: AppColors.text,
    fontVariant: ['tabular-nums'],
  },
  clockDate: {
    fontSize: 14,
    color: AppColors.textMuted,
    marginTop: Spacing.one,
    textTransform: 'capitalize',
  },
  hoursCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: AppColors.brand,
    borderRadius: Radius.lg,
    padding: Spacing.four,
  },
  hoursLabel: {
    color: AppColors.onBrand,
    fontSize: 15,
    fontWeight: '700',
  },
  hoursValue: {
    color: AppColors.onBrand,
    fontSize: 32,
    fontWeight: '800',
    fontVariant: ['tabular-nums'],
  },
  shiftCard: {
    gap: Spacing.three,
  },
  shiftStatusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
  },
  dot: {
    width: 10,
    height: 10,
    borderRadius: Radius.full,
  },
  shiftStatusText: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.text,
  },
  shiftMeta: {
    fontSize: 13,
    color: AppColors.textMuted,
    marginTop: -Spacing.one,
  },
  section: {
    gap: Spacing.two,
  },
  logList: {
    paddingVertical: Spacing.one,
  },
  logRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: Spacing.three,
  },
  logRowDivider: {
    borderTopWidth: 1,
    borderTopColor: AppColors.border,
  },
  logInfo: {
    flex: 1,
    gap: 2,
  },
  logPrimary: {
    fontSize: 14,
    fontWeight: '600',
    color: AppColors.text,
  },
  logSecondary: {
    fontSize: 12,
    color: AppColors.textFaint,
  },
  logDuration: {
    fontSize: 14,
    fontWeight: '700',
    color: AppColors.textSecondary,
    fontVariant: ['tabular-nums'],
  },
});
