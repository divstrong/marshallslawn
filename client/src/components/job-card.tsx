import { StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Card, StatusBadge } from '@/components/ui';
import { AppColors, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { formatDateShort } from '@/lib/format';
import type { Job } from '@/lib/types';

/** A job summary card used in the Jobs list. */
export function JobCard({ job, onPress }: { job: Job; onPress: () => void }) {
  const { t } = useLanguage();
  const address = job.property?.full_address ?? t('jobs.noAddress');
  const isHighPriority = job.priority?.toLowerCase() === 'high';

  return (
    <Card onPress={onPress} style={styles.card}>
      <View style={styles.topRow}>
        <Text style={styles.title} numberOfLines={1}>
          {job.title}
        </Text>
        <StatusBadge status={job.status} />
      </View>

      {job.customer ? (
        <Text style={styles.customer} numberOfLines={1}>
          {job.customer.name}
        </Text>
      ) : null}

      <View style={styles.metaRow}>
        <Icon name="location-outline" size={14} color={AppColors.textFaint} />
        <Text style={styles.meta} numberOfLines={1}>
          {address}
        </Text>
      </View>

      <View style={styles.footer}>
        <View style={styles.metaRow}>
          <Icon name="calendar-outline" size={14} color={AppColors.textFaint} />
          <Text style={styles.meta}>{formatDateShort(job.scheduled_date)}</Text>
        </View>
        {isHighPriority ? (
          <View style={styles.metaRow}>
            <Icon name="flag" size={14} color={AppColors.danger} />
            <Text style={[styles.meta, { color: AppColors.danger }]}>{t('jobs.highPriority')}</Text>
          </View>
        ) : null}
      </View>
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    gap: Spacing.two,
  },
  topRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  title: {
    flex: 1,
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
    flexShrink: 1,
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
});
