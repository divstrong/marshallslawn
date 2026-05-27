import { useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, View } from 'react-native';

import { JobCard } from '@/components/job-card';
import { EmptyState, ErrorState, FilterTabs, LoadingState, ScreenHeader } from '@/components/ui';
import { AppColors, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { api } from '@/lib/api';

export default function JobsScreen() {
  const router = useRouter();
  const { employee } = useAuth();
  const { t } = useLanguage();
  const [filter, setFilter] = useState('all');
  const [refreshing, setRefreshing] = useState(false);

  const { data, loading, error, reload } = useApiResource(() => api.jobs(filter), [filter]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  }, [reload]);

  const filters = [
    { label: t('jobs.filterAll'), value: 'all' },
    { label: t('jobs.filterScheduled'), value: 'scheduled' },
    { label: t('jobs.filterInProgress'), value: 'in_progress' },
    { label: t('jobs.filterCompleted'), value: 'completed' },
  ];

  const jobs = data ?? [];
  const subtitle =
    employee?.role === 'estimator' ? t('jobs.subtitleAll') : t('jobs.subtitleCrew');
  const countSubtitle = t(jobs.length === 1 ? 'jobs.countOne' : 'jobs.countMany', {
    n: jobs.length,
  });

  return (
    <View style={styles.screen}>
      <ScreenHeader title={t('jobs.title')} subtitle={data ? countSubtitle : subtitle} />

      <View style={styles.filters}>
        <FilterTabs options={filters} value={filter} onChange={setFilter} />
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
            <LoadingState label={t('jobs.loading')} />
          ) : error ? (
            <ErrorState message={error} onRetry={reload} />
          ) : (
            <EmptyState
              icon="briefcase-outline"
              title={t('jobs.emptyTitle')}
              message={t('jobs.emptyMsg')}
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
