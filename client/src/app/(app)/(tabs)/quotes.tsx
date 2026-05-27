import { useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import {
  Card,
  EmptyState,
  ErrorState,
  FilterTabs,
  HeaderButton,
  LoadingState,
  ScreenHeader,
  StatusBadge,
} from '@/components/ui';
import { AppColors, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { api } from '@/lib/api';
import { formatDateShort, formatMoney } from '@/lib/format';
import type { Quote } from '@/lib/types';

export default function QuotesScreen() {
  const router = useRouter();
  const { t } = useLanguage();
  const [filter, setFilter] = useState('all');
  const [refreshing, setRefreshing] = useState(false);

  const { data, loading, error, reload } = useApiResource(
    () => api.quotes(filter === 'all' ? undefined : filter),
    [filter],
  );

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  }, [reload]);

  const filters = [
    { label: t('quotes.filterAll'), value: 'all' },
    { label: t('quotes.filterDraft'), value: 'draft' },
    { label: t('quotes.filterSent'), value: 'sent' },
    { label: t('quotes.filterAccepted'), value: 'accepted' },
    { label: t('quotes.filterDeclined'), value: 'declined' },
  ];

  const quotes = data ?? [];
  const countSubtitle = t(quotes.length === 1 ? 'quotes.countOne' : 'quotes.countMany', {
    n: quotes.length,
  });

  return (
    <View style={styles.screen}>
      <ScreenHeader
        title={t('quotes.title')}
        subtitle={data ? countSubtitle : t('quotes.subtitle')}
        right={<HeaderButton icon="add" onPress={() => router.push('/quote/new')} />}
      />

      <View style={styles.filters}>
        <FilterTabs options={filters} value={filter} onChange={setFilter} />
      </View>

      <FlatList
        data={quotes}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.list}
        renderItem={({ item }) => (
          <QuoteCard quote={item} onPress={() => router.push(`/quote/${item.id}`)} />
        )}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          loading ? (
            <LoadingState label={t('quotes.loading')} />
          ) : error ? (
            <ErrorState message={error} onRetry={reload} />
          ) : (
            <EmptyState
              icon="document-text-outline"
              title={t('quotes.emptyTitle')}
              message={t('quotes.emptyMsg')}
            />
          )
        }
      />
    </View>
  );
}

function QuoteCard({ quote, onPress }: { quote: Quote; onPress: () => void }) {
  const { t } = useLanguage();
  return (
    <Card onPress={onPress} style={styles.card}>
      <View style={styles.topRow}>
        <Text style={styles.number}>{quote.estimate_number}</Text>
        <StatusBadge status={quote.status} />
      </View>
      {quote.customer ? (
        <Text style={styles.customer} numberOfLines={1}>
          {quote.customer.name}
        </Text>
      ) : null}
      {quote.property?.full_address ? (
        <View style={styles.metaRow}>
          <Icon name="location-outline" size={14} color={AppColors.textFaint} />
          <Text style={styles.meta} numberOfLines={1}>
            {quote.property.full_address}
          </Text>
        </View>
      ) : null}
      <View style={styles.footer}>
        <Text style={styles.total}>{formatMoney(quote.total)}</Text>
        <Text style={styles.meta}>
          {quote.valid_until
            ? t('quotes.validUntil', { date: formatDateShort(quote.valid_until) })
            : t('quotes.created', { date: formatDateShort(quote.created_at) })}
        </Text>
      </View>
    </Card>
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
  card: {
    gap: Spacing.two,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  number: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.text,
  },
  customer: {
    fontSize: 14,
    color: AppColors.textSecondary,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  meta: {
    fontSize: 13,
    color: AppColors.textMuted,
    flexShrink: 1,
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: Spacing.one,
  },
  total: {
    fontSize: 18,
    fontWeight: '800',
    color: AppColors.brand,
  },
});
