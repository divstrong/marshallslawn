import { useRouter } from 'expo-router';
import { useCallback, useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, StyleSheet, TextInput, View } from 'react-native';

import { Icon } from '@/components/icon';
import { JobCard } from '@/components/job-card';
import { EmptyState, ErrorState, FilterTabs, LoadingState, ScreenHeader } from '@/components/ui';
import { AppColors, MaxListWidth, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { padToColumns, useContentWidth, useLayout } from '@/hooks/use-layout';
import { api } from '@/lib/api';
import { formatDateLong, formatDateShort } from '@/lib/format';
import type { Job } from '@/lib/types';

/** Searchable text for a job — customer, property, services, and dates. */
function jobHaystack(job: Job): string {
  const parts: (string | null | undefined)[] = [
    job.title,
    job.customer?.name,
    job.property?.full_address,
    job.property?.city,
    job.scheduled_date,
    job.scheduled_date ? formatDateShort(job.scheduled_date) : null,
    job.scheduled_date ? formatDateLong(job.scheduled_date) : null,
    ...(job.services?.map((service) => service.name) ?? []),
  ];
  return parts.filter(Boolean).join(' ').toLowerCase();
}

export default function JobsScreen() {
  const router = useRouter();
  const { employee } = useAuth();
  const { t } = useLanguage();
  const { columns, gutter, isTablet } = useLayout();
  const contentWidth = useContentWidth(MaxListWidth);
  const [filter, setFilter] = useState('all');
  const [search, setSearch] = useState('');
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
  const query = search.trim().toLowerCase();
  const visibleJobs = useMemo(
    () => (query ? jobs.filter((job) => jobHaystack(job).includes(query)) : jobs),
    [jobs, query],
  );

  const subtitle =
    employee?.role === 'estimator' ? t('jobs.subtitleAll') : t('jobs.subtitleCrew');
  const countSubtitle = t(visibleJobs.length === 1 ? 'jobs.countOne' : 'jobs.countMany', {
    n: visibleJobs.length,
  });

  // Blanks keep the last grid row's cards the same width as a full row.
  const cells = useMemo(() => padToColumns(visibleJobs, columns), [visibleJobs, columns]);

  return (
    <View style={styles.screen}>
      <ScreenHeader title={t('jobs.title')} subtitle={data ? countSubtitle : subtitle} />

      <View
        style={[
          styles.filters,
          isTablet && styles.filtersWide,
          { paddingHorizontal: gutter },
          contentWidth,
        ]}
      >
        <View style={isTablet ? styles.filterTabsWrap : undefined}>
          <FilterTabs options={filters} value={filter} onChange={setFilter} />
        </View>
        <View style={[styles.searchRow, isTablet && styles.searchRowWide]}>
          <Icon name="search" size={18} color={AppColors.textFaint} />
          <TextInput
            style={styles.searchInput}
            placeholder={t('jobs.searchPlaceholder')}
            placeholderTextColor={AppColors.textFaint}
            value={search}
            onChangeText={setSearch}
            autoCapitalize="none"
            autoCorrect={false}
            returnKeyType="search"
            clearButtonMode="while-editing"
          />
          {search.length > 0 ? (
            <Pressable onPress={() => setSearch('')} hitSlop={8}>
              <Icon name="close-circle" size={18} color={AppColors.textFaint} />
            </Pressable>
          ) : null}
        </View>
      </View>

      <FlatList
        // FlatList cannot change `numColumns` in place — remount it instead.
        key={columns}
        data={cells}
        numColumns={columns}
        columnWrapperStyle={columns > 1 ? styles.column : undefined}
        keyExtractor={(item, index) => (item ? String(item.id) : `blank-${index}`)}
        contentContainerStyle={[styles.list, { padding: gutter }, contentWidth]}
        keyboardShouldPersistTaps="handled"
        renderItem={({ item }) =>
          item ? (
            <View style={styles.cell}>
              <JobCard job={item} onPress={() => router.push(`/job/${item.id}`)} />
            </View>
          ) : (
            <View style={styles.cell} />
          )
        }
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          loading ? (
            <LoadingState label={t('jobs.loading')} />
          ) : error ? (
            <ErrorState message={error} onRetry={reload} />
          ) : query ? (
            <EmptyState
              icon="search-outline"
              title={t('jobs.noMatchTitle')}
              message={t('jobs.noMatchMsg')}
            />
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
    paddingTop: Spacing.three,
    gap: Spacing.three,
  },
  // Tablets have room for the pills and the search field on one line.
  filtersWide: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  filterTabsWrap: {
    flex: 1,
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
    height: 44,
    paddingHorizontal: Spacing.three,
    backgroundColor: AppColors.surface,
    borderRadius: Radius.md,
    borderWidth: 1,
    borderColor: AppColors.border,
  },
  searchRowWide: {
    width: 320,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    color: AppColors.text,
    paddingVertical: 0,
    // Without an explicit value RN leaves kerning off the iOS text attributes,
    // and UIKit spaces the placeholder out on device. Pinning it to 0 writes an
    // explicit zero kern so the placeholder tracks like every other label.
    letterSpacing: 0,
  },
  list: {
    gap: Spacing.three,
    flexGrow: 1,
  },
  column: {
    gap: Spacing.three,
  },
  cell: {
    flex: 1,
  },
});
