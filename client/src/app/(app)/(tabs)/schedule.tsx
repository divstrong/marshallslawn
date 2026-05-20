import { useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Card, EmptyState, ErrorState, LoadingState, ScreenHeader, StatusBadge } from '@/components/ui';
import { AppColors, Radius, Spacing, statusTone } from '@/constants/theme';
import { useApiResource } from '@/hooks/use-async';
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

  return (
    <View style={styles.screen}>
      <ScreenHeader title="Schedule" subtitle="Your crew's route" />

      <ScrollView
        contentContainerStyle={styles.body}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {/* Date navigator */}
        <Card style={styles.dateNav} padded={false}>
          <Pressable onPress={() => setDate(shiftDate(date, -1))} hitSlop={8} style={styles.dateArrow}>
            <Icon name="chevron-back" size={22} color={AppColors.textMuted} />
          </Pressable>
          <Pressable onPress={() => setDate(todayString())} style={styles.dateCenter}>
            <Text style={styles.dateText}>{formatDateFull(date)}</Text>
            <Text style={styles.dateTag}>{isToday ? 'Today' : 'Tap to return to today'}</Text>
          </Pressable>
          <Pressable onPress={() => setDate(shiftDate(date, 1))} hitSlop={8} style={styles.dateArrow}>
            <Icon name="chevron-forward" size={22} color={AppColors.textMuted} />
          </Pressable>
        </Card>

        {/* Job count */}
        <View style={styles.countCard}>
          <View>
            <Text style={styles.countLabel}>Jobs Scheduled</Text>
            <Text style={styles.countHint}>In route order</Text>
          </View>
          <Text style={styles.countValue}>{data ? jobs.length : '—'}</Text>
        </View>

        {/* Route timeline */}
        {loading ? (
          <LoadingState label="Loading route…" />
        ) : error ? (
          <ErrorState message={error} onRetry={reload} />
        ) : jobs.length === 0 ? (
          <EmptyState
            icon="calendar-outline"
            title="No jobs scheduled"
            message="There are no jobs on the route for this day."
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
  const tone = statusTone(job.status);
  const done = job.status === 'completed';
  const address = job.property?.full_address;

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
    padding: Spacing.four,
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
  countCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: AppColors.brand,
    borderRadius: Radius.lg,
    padding: Spacing.four,
  },
  countLabel: {
    color: AppColors.onBrand,
    fontSize: 15,
    fontWeight: '700',
  },
  countHint: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 12,
    marginTop: 2,
  },
  countValue: {
    color: AppColors.onBrand,
    fontSize: 34,
    fontWeight: '800',
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
});
