import { useLocalSearchParams, useRouter } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  Alert,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { Icon } from '@/components/icon';
import { JobPhotos } from '@/components/job-photos';
import {
  Button,
  Card,
  ErrorState,
  LoadingState,
  ScreenHeader,
  SectionLabel,
  StatusBadge,
  TextField,
} from '@/components/ui';
import { AppColors, formatStatus, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { api } from '@/lib/api';
import { formatDateLong, formatDuration, formatTime } from '@/lib/format';
import { openMaps, openPhone } from '@/lib/links';
import type { Job } from '@/lib/types';

function pad(value: number): string {
  return String(value).padStart(2, '0');
}

/** "1:24:07" elapsed since an ISO timestamp. */
function stopwatch(startedAt: string, now: number): string {
  const seconds = Math.max(0, Math.floor((now - new Date(startedAt).getTime()) / 1000));
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return `${h}:${pad(m)}:${pad(s)}`;
}

export default function JobDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const jobId = Number(id);
  const router = useRouter();
  const { employee } = useAuth();
  const { t } = useLanguage();

  const { data: job, loading, error, reload } = useApiResource(() => api.job(jobId), [jobId]);

  const [now, setNow] = useState(() => Date.now());
  const [refreshing, setRefreshing] = useState(false);
  const [busy, setBusy] = useState(false);
  const [note, setNote] = useState('');

  // Tick for the live job timer.
  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await reload();
    setRefreshing(false);
  }, [reload]);

  const runAction = useCallback(
    async (action: 'start' | 'complete') => {
      setBusy(true);
      try {
        if (action === 'start') {
          await api.startJob(jobId);
        } else {
          await api.completeJob(jobId);
        }
        await reload();
      } catch (e) {
        Alert.alert(t('job.title'), e instanceof Error ? e.message : t('common.somethingWrong'));
      } finally {
        setBusy(false);
      }
    },
    [jobId, reload, t],
  );

  const submitNote = useCallback(async () => {
    const body = note.trim();
    if (!body) return;
    setBusy(true);
    try {
      await api.addJobNote(jobId, body);
      setNote('');
      await reload();
    } catch (e) {
      Alert.alert(t('job.notes'), e instanceof Error ? e.message : t('common.somethingWrong'));
    } finally {
      setBusy(false);
    }
  }, [jobId, note, reload, t]);

  return (
    <View style={styles.screen}>
      <ScreenHeader
        title={job?.title ?? t('job.title')}
        subtitle={job?.customer?.name ?? undefined}
        onBack={() => router.back()}
      />

      {loading ? (
        <LoadingState label={t('job.loading')} />
      ) : error || !job ? (
        <ErrorState message={error ?? t('job.notFound')} onRetry={reload} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.body}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        >
          <JobTimerCard
            job={job}
            now={now}
            busy={busy}
            isForeman={employee?.role === 'foreman'}
            onStart={() => runAction('start')}
            onComplete={() =>
              Alert.alert(t('job.completeTitle'), t('job.completeMsg'), [
                { text: t('common.cancel'), style: 'cancel' },
                { text: t('job.complete'), onPress: () => runAction('complete') },
              ])
            }
          />

          <DetailGrid job={job} />

          {job.customer ? (
            <View style={styles.section}>
              <SectionLabel>{t('job.customer')}</SectionLabel>
              <Card style={styles.gap}>
                <Text style={styles.cardHeading}>{job.customer.name}</Text>
                {job.customer.phone ? (
                  <Pressable
                    style={styles.linkRow}
                    onPress={() => openPhone(job.customer!.phone!)}
                  >
                    <Icon name="call-outline" size={16} color={AppColors.brand} />
                    <Text style={styles.linkText}>{job.customer.phone}</Text>
                  </Pressable>
                ) : null}
              </Card>
            </View>
          ) : null}

          {job.property ? (
            <View style={styles.section}>
              <SectionLabel>{t('job.property')}</SectionLabel>
              <Card style={styles.gap}>
                <Text style={styles.cardHeading}>{job.property.address ?? t('job.property')}</Text>
                {job.property.city ? (
                  <Text style={styles.cardMuted}>
                    {[job.property.city, job.property.state].filter(Boolean).join(', ')}{' '}
                    {job.property.zip ?? ''}
                  </Text>
                ) : null}
                <Pressable
                  style={styles.linkRow}
                  onPress={() => openMaps(job.property!.full_address)}
                >
                  <Icon name="navigate-outline" size={16} color={AppColors.brand} />
                  <Text style={styles.linkText}>{t('common.navigate')}</Text>
                </Pressable>
              </Card>
            </View>
          ) : null}

          {job.description ? (
            <View style={styles.section}>
              <SectionLabel>{t('job.description')}</SectionLabel>
              <Card>
                <Text style={styles.bodyText}>{job.description}</Text>
              </Card>
            </View>
          ) : null}

          {/* Services */}
          <View style={styles.section}>
            <SectionLabel>{t('job.services')}</SectionLabel>
            <Card style={styles.gap}>
              {job.services && job.services.length > 0 ? (
                job.services.map((service, index) => (
                  <View
                    key={service.id}
                    style={[styles.noteRow, index > 0 && styles.noteRowDivider]}
                  >
                    <View style={styles.linkRow}>
                      <Icon name="construct-outline" size={16} color={AppColors.brand} />
                      <Text style={styles.cardHeading}>{service.name}</Text>
                    </View>
                    {service.description ? (
                      <Text style={styles.bodyText}>{service.description}</Text>
                    ) : null}
                  </View>
                ))
              ) : (
                <Text style={styles.cardMuted}>{t('job.noServices')}</Text>
              )}
            </Card>
          </View>

          {/* Photos */}
          <View style={styles.section}>
            <SectionLabel>{t('job.photos')}</SectionLabel>
            <JobPhotos jobId={jobId} media={job.media} onChanged={reload} />
          </View>

          {/* Notes */}
          <View style={styles.section}>
            <SectionLabel>{t('job.notes')}</SectionLabel>
            <Card style={styles.gap}>
              {job.messages.length === 0 ? (
                <Text style={styles.cardMuted}>{t('job.noNotes')}</Text>
              ) : (
                job.messages.map((message, index) => (
                  <View
                    key={message.id}
                    style={[styles.noteRow, index > 0 && styles.noteRowDivider]}
                  >
                    <Text style={styles.bodyText}>{message.body}</Text>
                    <Text style={styles.noteMeta}>
                      {message.sender ?? t('job.notes')} · {message.created_human ?? ''}
                    </Text>
                  </View>
                ))
              )}
              <TextField
                placeholder={t('job.addNotePlaceholder')}
                value={note}
                onChangeText={setNote}
                multiline
                style={styles.noteInput}
              />
              <Button
                label={t('job.addNote')}
                icon="chatbubble-outline"
                variant="secondary"
                onPress={submitNote}
                disabled={busy || note.trim().length === 0}
              />
            </Card>
          </View>
        </ScrollView>
      )}
    </View>
  );
}

/* -------------------------------------------------------------------------- */
/* Foreman job timer                                                          */
/* -------------------------------------------------------------------------- */

interface JobTimerCardProps {
  job: Job;
  now: number;
  busy: boolean;
  isForeman: boolean;
  onStart: () => void;
  onComplete: () => void;
}

function JobTimerCard({ job, now, busy, isForeman, onStart, onComplete }: JobTimerCardProps) {
  const { t } = useLanguage();
  const completed = job.status === 'completed';
  const inProgress = job.status === 'in_progress';

  return (
    <Card style={styles.timerCard}>
      <View style={styles.timerHeader}>
        <Text style={styles.timerLabel}>{t('job.statusLabel')}</Text>
        <StatusBadge status={job.status} />
      </View>

      {inProgress && job.started_at ? (
        <>
          <Text style={styles.timerValue}>{stopwatch(job.started_at, now)}</Text>
          <Text style={styles.timerMeta}>
            {t('job.startedAt', { time: formatTime(job.started_at) })}
          </Text>
        </>
      ) : completed ? (
        <View style={styles.timerSummary}>
          <TimePair label={t('job.startedField')} value={formatTime(job.started_at)} />
          <TimePair label={t('job.finishedField')} value={formatTime(job.finished_at)} />
        </View>
      ) : (
        <Text style={styles.timerMeta}>{t('job.notStarted')}</Text>
      )}

      {isForeman ? (
        <View style={styles.timerActions}>
          {!inProgress && !completed ? (
            <Button
              label={t('job.startJob')}
              icon="play"
              variant="success"
              loading={busy}
              onPress={onStart}
            />
          ) : null}
          {inProgress ? (
            <Button
              label={t('job.completeJob')}
              icon="checkmark-done"
              variant="primary"
              loading={busy}
              onPress={onComplete}
            />
          ) : null}
        </View>
      ) : null}
    </Card>
  );
}

function TimePair({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.timePair}>
      <Text style={styles.timePairLabel}>{label}</Text>
      <Text style={styles.timePairValue}>{value}</Text>
    </View>
  );
}

/* -------------------------------------------------------------------------- */
/* Info grid                                                                  */
/* -------------------------------------------------------------------------- */

function DetailGrid({ job }: { job: Job }) {
  const { t } = useLanguage();
  const priority = job.priority || 'normal';
  const cells = [
    {
      label: t('job.priority'),
      value: t(`priority.${priority}`, undefined, formatStatus(priority)),
    },
    { label: t('job.scheduled'), value: formatDateLong(job.scheduled_date) },
    { label: t('job.crew'), value: job.crew?.name ?? '—' },
    {
      label: t('job.duration'),
      value:
        job.started_at && job.finished_at
          ? formatDuration(
              (new Date(job.finished_at).getTime() - new Date(job.started_at).getTime()) / 60000,
            )
          : '—',
    },
  ];

  return (
    <View style={styles.grid}>
      {cells.map((cell) => (
        <View key={cell.label} style={styles.gridCell}>
          <Text style={styles.gridLabel}>{cell.label}</Text>
          <Text style={styles.gridValue}>{cell.value}</Text>
        </View>
      ))}
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
  section: {
    gap: Spacing.two,
  },
  gap: {
    gap: Spacing.two,
  },
  timerCard: {
    gap: Spacing.three,
  },
  timerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  timerLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: AppColors.textFaint,
    letterSpacing: 0.4,
  },
  timerValue: {
    fontSize: 44,
    fontWeight: '800',
    color: AppColors.brand,
    fontVariant: ['tabular-nums'],
    textAlign: 'center',
  },
  timerMeta: {
    fontSize: 13,
    color: AppColors.textMuted,
    textAlign: 'center',
  },
  timerSummary: {
    flexDirection: 'row',
    gap: Spacing.three,
  },
  timePair: {
    flex: 1,
    backgroundColor: AppColors.surfaceMuted,
    borderRadius: Radius.md,
    padding: Spacing.three,
  },
  timePairLabel: {
    fontSize: 12,
    color: AppColors.textFaint,
    fontWeight: '600',
  },
  timePairValue: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.text,
    marginTop: 2,
  },
  timerActions: {
    gap: Spacing.two,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: Spacing.three,
  },
  gridCell: {
    flexGrow: 1,
    flexBasis: '46%',
    backgroundColor: AppColors.surface,
    borderWidth: 1,
    borderColor: AppColors.border,
    borderRadius: Radius.md,
    padding: Spacing.three,
  },
  gridLabel: {
    fontSize: 12,
    color: AppColors.textFaint,
    fontWeight: '600',
  },
  gridValue: {
    fontSize: 15,
    fontWeight: '700',
    color: AppColors.text,
    marginTop: 3,
  },
  cardHeading: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.text,
  },
  cardMuted: {
    fontSize: 13,
    color: AppColors.textMuted,
  },
  bodyText: {
    fontSize: 14,
    color: AppColors.textSecondary,
    lineHeight: 20,
  },
  linkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  linkText: {
    fontSize: 14,
    fontWeight: '600',
    color: AppColors.brand,
  },
  noteRow: {
    paddingBottom: Spacing.two,
  },
  noteRowDivider: {
    borderTopWidth: 1,
    borderTopColor: AppColors.border,
    paddingTop: Spacing.two,
  },
  noteMeta: {
    fontSize: 12,
    color: AppColors.textFaint,
    marginTop: 3,
  },
  noteInput: {
    minHeight: 72,
    textAlignVertical: 'top',
  },
});
