import { useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Card, EmptyState, ErrorState, LoadingState, ScreenHeader, StatusBadge } from '@/components/ui';
import { WeatherWidget } from '@/components/weather-widget';
import { AppColors, Radius, Spacing, statusTone } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { useContentWidth, useLayout } from '@/hooks/use-layout';
import { api } from '@/lib/api';
import { formatDateFull } from '@/lib/format';
import { openMaps } from '@/lib/links';
import type { Job } from '@/lib/types';

/** Local YYYY-MM-DD for today. */
function todayString(): string {
  const now = new Date();
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function pad(value: number): string {
  return String(value).padStart(2, '0');
}

/** Shift a YYYY-MM-DD string by a number of days. */
function shiftDate(value: string, days: number): string {
  const date = new Date(`${value}T00:00:00`);
  date.setDate(date.getDate() + days);
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export default function ScheduleScreen() {
  const router = useRouter();
  const { t } = useLanguage();
  const { gutter } = useLayout();
  const contentWidth = useContentWidth();
  const [date, setDate] = useState(todayString());
  const [refreshing, setRefreshing] = useState(false);

  const { data, loading, error, reload } = useApiResource(() => api.schedule(date), [date]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  }, [reload]);

  const jobs = data?.jobs ?? [];
  const isToday = date === todayString();
  const completedCount = jobs.filter((job) => job.status === 'completed').length;
  const remaining = jobs.length - completedCount;

  return (
    <View style={styles.screen}>
      <ScreenHeader
        title={t('schedule.title')}
        subtitle={t('schedule.jobsRemaining')}
        right={<Text style={styles.headerCount}>{data ? remaining : '—'}</Text>}
      />

      <ScrollView
        contentContainerStyle={[styles.body, { padding: gutter }, contentWidth]}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {/* Current weather for the crew's location */}
        <WeatherWidget />

        {/* Date navigator */}
        <Card style={styles.dateNav} padded={false}>
          <Pressable onPress={() => setDate(shiftDate(date, -1))} hitSlop={8} style={styles.dateArrow}>
            <Icon name="chevron-back" size={22} color={AppColors.textMuted} />
          </Pressable>
          <Pressable onPress={() => setDate(todayString())} style={styles.dateCenter}>
            <Text style={styles.dateText}>{formatDateFull(date)}</Text>
            <Text style={styles.dateTag}>
              {isToday ? t('schedule.today') : t('schedule.tapToday')}
            </Text>
          </Pressable>
          <Pressable onPress={() => setDate(shiftDate(date, 1))} hitSlop={8} style={styles.dateArrow}>
            <Icon name="chevron-forward" size={22} color={AppColors.textMuted} />
          </Pressable>
        </Card>

        {/* Route timeline */}
        {loading ? (
          <LoadingState label={t('schedule.loading')} />
        ) : error ? (
          <ErrorState message={error} onRetry={reload} />
        ) : jobs.length === 0 ? (
          <EmptyState
            icon="calendar-outline"
            title={t('schedule.emptyTitle')}
            message={t('schedule.emptyMsg')}
          />
        ) : (
          <View style={styles.timeline}>
            {jobs.map((job, index) => (
              <RouteStopRow
                key={job.id}
                job={job}
                position={index + 1}
                isLast={index === jobs.length - 1}
                onPress={() => router.push(`/job/${job.id}`)}
              />
            ))}
          </View>
        )}
      </ScrollView>
    </View>
  );
}

interface RouteStopRowProps {
  job: Job;
  position: number;
  isLast: boolean;
  onPress: () => void;
}

function RouteStopRow({ job, position, isLast, onPress }: RouteStopRowProps) {
  const { employee } = useAuth();
  const tone = statusTone(job.status);
  const done = job.status === 'completed';
  const address = job.property?.full_address;

  // Crew members (tech / field) get directions from the job detail view's map,
  // so the address here is plain text and the whole card opens that detail.
  const linkToMap = employee?.role !== 'spray_tech' && employee?.role !== 'field';

  return (
    <View style={styles.stopRow}>
      <View style={styles.rail}>
        <View style={[styles.marker, { backgroundColor: tone.fg }]}>
          {done ? (
            <Icon name="checkmark" size={16} color={AppColors.onBrand} />
          ) : (
            <Text style={styles.markerText}>{position}</Text>
          )}
        </View>
        {!isLast ? <View style={styles.railLine} /> : null}
      </View>

      <Card onPress={onPress} style={styles.stopCard}>
        <View style={styles.stopTop}>
          <Text style={styles.stopTitle} numberOfLines={1}>
            {job.title}
          </Text>
          <StatusBadge status={job.status} />
        </View>
        {job.customer ? <Text style={styles.stopCustomer}>{job.customer.name}</Text> : null}
        {address ? (
          linkToMap ? (
            <Pressable
              onPress={() => openMaps(address)}
              style={styles.navRow}
              hitSlop={6}
            >
              <Icon name="navigate-outline" size={14} color={AppColors.brand} />
              <Text style={styles.navText} numberOfLines={1}>
                {address}
              </Text>
            </Pressable>
          ) : (
            <View style={styles.navRow}>
              <Icon name="location-outline" size={14} color={AppColors.textFaint} />
              <Text style={styles.navTextPlain} numberOfLines={1}>
                {address}
              </Text>
            </View>
          )
        ) : null}
      </Card>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  body: {
    gap: Spacing.three,
    flexGrow: 1,
  },
  dateNav: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  dateArrow: {
    padding: Spacing.three,
  },
  dateCenter: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: Spacing.three,
  },
  dateText: {
    fontSize: 15,
    fontWeight: '700',
    color: AppColors.text,
  },
  dateTag: {
    fontSize: 12,
    color: AppColors.textFaint,
    marginTop: 2,
  },
  headerCount: {
    color: AppColors.onBrand,
    fontSize: 30,
    fontWeight: '800',
    fontVariant: ['tabular-nums'],
  },
  timeline: {
    marginTop: Spacing.one,
  },
  stopRow: {
    flexDirection: 'row',
    alignItems: 'stretch',
    gap: Spacing.three,
  },
  rail: {
    width: 32,
    alignItems: 'center',
  },
  marker: {
    width: 32,
    height: 32,
    borderRadius: Radius.full,
    alignItems: 'center',
    justifyContent: 'center',
  },
  markerText: {
    color: AppColors.onBrand,
    fontSize: 14,
    fontWeight: '800',
  },
  railLine: {
    flex: 1,
    width: 2,
    backgroundColor: AppColors.border,
    marginVertical: 2,
  },
  stopCard: {
    flex: 1,
    marginBottom: Spacing.three,
    gap: Spacing.two,
  },
  stopTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  stopTitle: {
    flex: 1,
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.text,
  },
  stopCustomer: {
    fontSize: 14,
    color: AppColors.textSecondary,
  },
  navRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  navText: {
    flex: 1,
    fontSize: 13,
    color: AppColors.brand,
    fontWeight: '500',
  },
  navTextPlain: {
    flex: 1,
    fontSize: 13,
    color: AppColors.textMuted,
  },
});
