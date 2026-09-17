/**
 * Prominent disclosure for location sharing.
 *
 * Google Play requires an in-app disclosure that names the data collected and
 * the feature it enables, shown *before* the runtime location prompt, with an
 * explicit affirmative action. The backdrop is deliberately inert so consent
 * is always a tap on "Continue" rather than a dismissal.
 *
 * iOS gets the same screen with the "even when the app is closed" wording
 * dropped, because it no longer collects location in the background — see
 * `lib/location.ts`.
 *
 * See `client/docs/play-review-notes.md`.
 */

import { Modal, Platform, Pressable, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Button } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';

interface LocationDisclosureProps {
  visible: boolean;
  onAccept: () => void;
  onDecline: () => void;
}

export function LocationDisclosure({ visible, onAccept, onDecline }: LocationDisclosureProps) {
  const { t } = useLanguage();

  const isIos = Platform.OS === 'ios';

  const points = [
    { icon: 'person-outline', text: t('location.discloseWho') },
    {
      icon: 'time-outline',
      text: t(isIos ? 'location.discloseWhenIos' : 'location.discloseWhen'),
    },
    { icon: 'log-out-outline', text: t('location.discloseStop') },
  ] as const;

  return (
    <Modal visible={visible} transparent animationType="fade" statusBarTranslucent>
      <View style={styles.backdrop}>
        {/* Swallows taps so the backdrop can't stand in for consent. */}
        <Pressable style={styles.card} onPress={() => {}}>
          <View style={styles.badge}>
            <Icon name="location" size={24} color={AppColors.brand} />
          </View>

          <Text style={styles.title}>{t('location.discloseTitle')}</Text>
          <Text style={styles.body}>
            {t(isIos ? 'location.discloseBodyIos' : 'location.discloseBody')}
          </Text>

          <View style={styles.points}>
            {points.map((point) => (
              <View key={point.icon} style={styles.point}>
                <Icon name={point.icon} size={16} color={AppColors.textMuted} />
                <Text style={styles.pointText}>{point.text}</Text>
              </View>
            ))}
          </View>

          <Button
            label={t('location.discloseAccept')}
            icon="checkmark-circle-outline"
            onPress={onAccept}
          />
          <Button label={t('location.discloseDecline')} variant="ghost" onPress={onDecline} />
        </Pressable>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: Spacing.five,
  },
  card: {
    width: '100%',
    maxWidth: 380,
    backgroundColor: AppColors.surface,
    borderRadius: Radius.lg,
    padding: Spacing.five,
    gap: Spacing.three,
  },
  badge: {
    width: 48,
    height: 48,
    borderRadius: Radius.full,
    backgroundColor: AppColors.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    color: AppColors.text,
  },
  body: {
    fontSize: 14,
    lineHeight: 21,
    color: AppColors.textSecondary,
  },
  points: {
    gap: Spacing.two,
    paddingVertical: Spacing.two,
  },
  point: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: Spacing.two,
  },
  pointText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
    color: AppColors.textMuted,
  },
});
