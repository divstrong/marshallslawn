import { useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, View } from 'react-native';

import { JobCard } from '@/components/job-card';
import { EmptyState, ErrorState, FilterTabs, LoadingState, ScreenHeader } from '@/components/ui';
import { AppColors, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useApiResource } from '@/hooks/use-async';
import { api } from '@/lib/api';

const FILTERS = [
  { label: 'All', value: 'all' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
];

export default function JobsScreen() {
  const router = useRouter();
  const { employee } = useAuth();
  const [filter, setFilter] = useState('all');
  const [refreshing, setRefreshing] = useState(false);

  const { data, loading, error, reload } = useApiResource(() => api.jobs(filter), [filter]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  }, [reload]);

  const jobs = data ?? [];
  const subtitle =
    employee?.role === 'estimator' ? 'All jobs' : 'Jobs assigned to your crew';

  return (
    <View style={styles.screen}>
      <ScreenHeader
        title="Jobs"
        subtitle={data ? `${jobs.length} ${jobs.length === 1 ? 'job' : 'jobs'}` : subtitle}
      />

      <View style={styles.filters}>
        <FilterTabs options={FILTERS} value={filter} onChange={setFilter} />
      </View>

      <FlatList
        data={jobs}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <JobCard job={item} onPress={() => router.push(`/job/${item.id}`)} />
        )}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          loading ? (
            <LoadingState label="Loading jobs…" />
          ) : error ? (
            <ErrorState message={error} onRetry={reload} />
          ) : (
            <EmptyState
              icon="briefcase-outline"
              title="No jobs"
              message="There are no jobs matching this filter."
            />
          )
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  filters: {
    paddingHorizontal: Spacing.four,
    paddingTop: Spacing.three,
  },
  list: {
    padding: Spacing.four,
    gap: Spacing.three,
    flexGrow: 1,
  },
});
