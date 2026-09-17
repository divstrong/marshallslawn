import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { Icon, type IconName } from '@/components/icon';
import {
  Badge,
  Button,
  Card,
  ConfirmModal,
  Divider,
  ScreenHeader,
  SectionLabel,
} from '@/components/ui';
import { APP_VERSION } from '@/constants/config';
import { AppColors, Brand, MaxContentWidth, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLanguage } from '@/context/language';
import { useLocationTracking } from '@/context/location';
import { useContentWidth, useLayout } from '@/hooks/use-layout';
import { api } from '@/lib/api';
import { formatTime } from '@/lib/format';
import { openMaps, openPhone } from '@/lib/links';
import { LANGUAGES } from '@/lib/translations';

export default function SettingsScreen() {
  const { employee, signOut, refresh } = useAuth();
  const { t, language, setLanguage } = useLanguage();
  const tracking = useLocationTracking();
  const { isExpanded, gutter } = useLayout();
  // Two columns need more room than the usual reading width.
  const contentWidth = useContentWidth(isExpanded ? 1000 : MaxContentWidth);
  const [signingOut, setSigningOut] = useState(false);
  const [signOutVisible, setSignOutVisible] = useState(false);
  const [disableLocationVisible, setDisableLocationVisible] = useState(false);
  const [disablingLocation, setDisablingLocation] = useState(false);
  const [avatarBusy, setAvatarBusy] = useState(false);

  if (!employee) {
    return null;
  }

  const initials = `${employee.first_name?.[0] ?? ''}${employee.last_name?.[0] ?? ''}`.toUpperCase();
  const address = [employee.address, [employee.city, employee.state].filter(Boolean).join(', ')]
    .filter(Boolean)
    .join(' · ');
  const roleLabel = t(
    `login.role${employee.role.charAt(0).toUpperCase()}${employee.role.slice(1)}`,
    undefined,
    employee.role_label,
  );

  const handleSignOut = async () => {
    setSigningOut(true);
    await signOut();
  };

  const applyAvatar = async (run: () => Promise<unknown>) => {
    setAvatarBusy(true);
    try {
      await run();
      await refresh();
    } catch (e) {
      Alert.alert('Marshall’s Lawn', e instanceof Error ? e.message : t('common.somethingWrong'));
    } finally {
      setAvatarBusy(false);
    }
  };

  const pickAvatar = async (source: 'camera' | 'library') => {
    try {
      let result: ImagePicker.ImagePickerResult;
      if (source === 'camera') {
        const permission = await ImagePicker.requestCameraPermissionsAsync();
        if (!permission.granted) {
          Alert.alert(t('settings.photoTitle'), t('common.cameraNeeded'));
          return;
        }
        result = await ImagePicker.launchCameraAsync({
          mediaTypes: ['images'],
          allowsEditing: true,
          aspect: [1, 1],
          quality: 0.7,
        });
      } else {
        result = await ImagePicker.launchImageLibraryAsync({
          mediaTypes: ['images'],
          allowsEditing: true,
          aspect: [1, 1],
          quality: 0.7,
        });
      }
      if (result.canceled) {
        return;
      }
      const asset = result.assets[0];
      await applyAvatar(() =>
        api.uploadAvatar({
          uri: asset.uri,
          name: asset.fileName ?? 'avatar.jpg',
          type: asset.mimeType ?? 'image/jpeg',
        }),
      );
    } catch (e) {
      Alert.alert('Marshall’s Lawn', e instanceof Error ? e.message : t('common.somethingWrong'));
    }
  };

  const editAvatar = () => {
    const actions: { text: string; style?: 'cancel' | 'destructive'; onPress?: () => void }[] = [
      { text: t('common.takePhoto'), onPress: () => pickAvatar('camera') },
      { text: t('common.chooseLibrary'), onPress: () => pickAvatar('library') },
    ];
    if (employee.avatar_url) {
      actions.push({
        text: t('settings.removePhoto'),
        style: 'destructive',
        onPress: () => applyAvatar(api.removeAvatar),
      });
    }
    actions.push({ text: t('common.cancel'), style: 'cancel' });
    Alert.alert(t('settings.photoTitle'), t('settings.photoMsg'), actions);
  };

  const locationText = tracking.active
    ? // iOS reports only while the app is open — see `lib/location.ts`.
      t(Platform.OS === 'ios' ? 'settings.locActiveIos' : 'settings.locActive')
    : !tracking.supported
      ? t('settings.locWeb')
      : tracking.paused
        ? t('settings.locPaused')
        : tracking.permission === 'denied'
          ? t('settings.locDenied')
          : t('settings.locPrompt');

  const handleDisableLocation = async () => {
    setDisablingLocation(true);
    try {
      await tracking.disable();
    } finally {
      setDisablingLocation(false);
      setDisableLocationVisible(false);
    }
  };

  /* Identity and how to reach this person. */
  const identity = (
    <>
      {/* Profile */}
      <Card style={styles.profileCard}>
        <Pressable onPress={editAvatar} disabled={avatarBusy} style={styles.avatarWrap}>
          {employee.avatar_url ? (
            <Image source={{ uri: employee.avatar_url }} style={styles.avatar} contentFit="cover" />
          ) : (
            <View style={[styles.avatar, styles.avatarFallback]}>
              <Text style={styles.avatarText}>{initials || '?'}</Text>
            </View>
          )}
          <View style={styles.avatarBadge}>
            {avatarBusy ? (
              <ActivityIndicator size="small" color={AppColors.onBrand} />
            ) : (
              <Icon name="camera" size={14} color={AppColors.onBrand} />
            )}
          </View>
        </Pressable>
        <Text style={styles.name}>{employee.name}</Text>
        {employee.email ? <Text style={styles.email}>{employee.email}</Text> : null}
        <View style={styles.badges}>
          <Badge label={roleLabel} bg={Brand[50]} fg={Brand[700]} />
          {employee.division ? (
            <Badge label={employee.division} bg={AppColors.border} fg={AppColors.textSecondary} />
          ) : null}
        </View>
      </Card>

      {/* Contact */}
      <View style={styles.section}>
        <SectionLabel>{t('settings.contactInfo')}</SectionLabel>
        <Card padded={false} style={styles.infoCard}>
          <InfoRow
            icon="call-outline"
            label={t('settings.phone')}
            value={employee.phone ?? t('settings.notOnFile')}
            onPress={employee.phone ? () => openPhone(employee.phone!) : undefined}
          />
          <Divider />
          <InfoRow
            icon="mail-outline"
            label={t('settings.email')}
            value={employee.email ?? t('settings.notOnFile')}
          />
          {address ? (
            <>
              <Divider />
              <InfoRow
                icon="location-outline"
                label={t('settings.address')}
                value={address}
                onPress={() => openMaps(address)}
              />
            </>
          ) : null}
        </Card>
      </View>
    </>
  );

  /* Preferences, device state, and the way out. */
  const preferences = (
    <>
      {/* Language */}
      <View style={styles.section}>
        <SectionLabel>{t('settings.language')}</SectionLabel>
        <Card padded={false} style={styles.infoCard}>
          {LANGUAGES.map((lang, index) => {
            const active = language === lang.code;
            return (
              <View key={lang.code}>
                {index > 0 ? <Divider /> : null}
                <Pressable
                  onPress={() => setLanguage(lang.code)}
                  style={({ pressed }) => (pressed ? styles.pressed : undefined)}
                >
                  <View style={styles.langRow}>
                    <Text style={styles.langLabel}>{lang.label}</Text>
                    {active ? (
                      <Icon name="checkmark-circle" size={24} color={AppColors.brand} />
                    ) : (
                      <View style={styles.langRadio} />
                    )}
                  </View>
                </Pressable>
              </View>
            );
          })}
        </Card>
      </View>

      {/* Location sharing (foreman only) */}
      {tracking.tracksThisRole ? (
        <View style={styles.section}>
          <SectionLabel>{t('settings.locationSharing')}</SectionLabel>
          <Card style={styles.locationCard}>
            <View style={styles.locationStatus}>
              <View
                style={[
                  styles.locationDot,
                  {
                    // Amber while degraded: on, but only "While Using".
                    backgroundColor:
                      tracking.active && tracking.permission === 'granted'
                        ? AppColors.success
                        : AppColors.warning,
                  },
                ]}
              />
              <Text style={styles.locationState}>
                {tracking.active ? t('settings.locOn') : t('settings.locOff')}
              </Text>
            </View>
            <Text style={styles.locationText}>{locationText}</Text>

            {/* Concrete proof that background reporting is reaching dispatch,
                rather than merely being permitted. */}
            {tracking.active ? (
              <View style={styles.locationProof}>
                <Icon name="navigate-circle-outline" size={16} color={AppColors.textMuted} />
                <Text style={styles.locationProofText}>
                  {tracking.lastSync
                    ? t('settings.locLastReport', {
                        time: formatTime(tracking.lastSync.at),
                        coords: `${tracking.lastSync.latitude.toFixed(4)}, ${tracking.lastSync.longitude.toFixed(4)}`,
                      })
                    : t('settings.locAwaitingFirst')}
                </Text>
              </View>
            ) : null}

            {/* Degraded Android grant: tracking runs, but the OS won't
                relaunch the app after a kill or reboot. Nudge toward "Allow
                all the time" rather than refusing to work. iOS never lands
                here — it asks only for "While Using" now. */}
            {tracking.active && tracking.permission === 'whenInUse' ? (
              <>
                <View style={styles.locationWarning}>
                  <Icon name="warning-outline" size={16} color={AppColors.warning} />
                  <Text style={styles.locationWarningText}>
                    {t('settings.locWhenInUseAndroid')}
                  </Text>
                </View>
                <Button
                  label={t('settings.openSettings')}
                  variant="secondary"
                  icon="settings-outline"
                  onPress={() => Linking.openSettings()}
                />
              </>
            ) : null}

            {/* The in-app off switch: stops tracking without the employee
                having to revoke the OS permission, and stays off across
                launches until they re-enable here. */}
            {tracking.supported && tracking.active ? (
              <Button
                label={t('settings.disableLocation')}
                variant="secondary"
                icon="location-outline"
                onPress={() => setDisableLocationVisible(true)}
              />
            ) : null}

            {tracking.supported && !tracking.active ? (
              tracking.permission === 'denied' && !tracking.paused ? (
                <Button
                  label={t('settings.openSettings')}
                  variant="secondary"
                  icon="settings-outline"
                  onPress={() => Linking.openSettings()}
                />
              ) : (
                <Button
                  label={t('settings.enableLocation')}
                  icon="location-outline"
                  onPress={tracking.enable}
                />
              )
            ) : null}
          </Card>
        </View>
      ) : null}

      {/* About */}
      <View style={styles.section}>
        <SectionLabel>{t('settings.about')}</SectionLabel>
        <Card padded={false} style={styles.infoCard}>
          <InfoRow
            icon="phone-portrait-outline"
            label={t('settings.appVersion')}
            value={APP_VERSION}
          />
        </Card>
      </View>

      <Button
        label={t('settings.signOut')}
        icon="log-out-outline"
        variant="danger"
        loading={signingOut}
        onPress={() => setSignOutVisible(true)}
        style={styles.signOut}
      />
    </>
  );

  return (
    <View style={styles.screen}>
      <ScreenHeader title={t('settings.title')} subtitle={t('settings.subtitle')} />

      <ScrollView contentContainerStyle={[styles.body, { padding: gutter }, contentWidth]}>
        {isExpanded ? (
          <View style={styles.columns}>
            <View style={styles.column}>{identity}</View>
            <View style={styles.column}>{preferences}</View>
          </View>
        ) : (
          <>
            {identity}
            {preferences}
          </>
        )}
      </ScrollView>

      <ConfirmModal
        visible={signOutVisible}
        title={t('settings.signOut')}
        message={t('settings.signOutConfirm')}
        confirmLabel={t('settings.signOut')}
        cancelLabel={t('common.cancel')}
        confirmVariant="danger"
        confirmIcon="log-out-outline"
        loading={signingOut}
        onConfirm={handleSignOut}
        onCancel={() => setSignOutVisible(false)}
      />

      <ConfirmModal
        visible={disableLocationVisible}
        title={t('settings.disableLocation')}
        message={t('settings.disableLocationConfirm')}
        confirmLabel={t('settings.locOff')}
        cancelLabel={t('common.cancel')}
        confirmVariant="danger"
        confirmIcon="location-outline"
        loading={disablingLocation}
        onConfirm={handleDisableLocation}
        onCancel={() => setDisableLocationVisible(false)}
      />
    </View>
  );
}

function InfoRow({
  icon,
  label,
  value,
  onPress,
}: {
  icon: IconName;
  label: string;
  value: string;
  onPress?: () => void;
}) {
  const content = (
    <View style={styles.infoRow}>
      <View style={styles.infoIcon}>
        <Icon name={icon} size={18} color={AppColors.brand} />
      </View>
      <View style={styles.infoText}>
        <Text style={styles.infoLabel}>{label}</Text>
        <Text style={styles.infoValue} numberOfLines={2}>
          {value}
        </Text>
      </View>
      {onPress ? <Icon name="chevron-forward" size={18} color={AppColors.textFaint} /> : null}
    </View>
  );

  if (onPress) {
    return (
      <Pressable onPress={onPress} style={({ pressed }) => (pressed ? styles.pressed : undefined)}>
        {content}
      </Pressable>
    );
  }
  return content;
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  body: {
    gap: Spacing.three,
  },
  columns: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: Spacing.five,
  },
  column: {
    flex: 1,
    gap: Spacing.three,
  },
  profileCard: {
    alignItems: 'center',
    gap: Spacing.one,
    paddingVertical: Spacing.five,
  },
  avatarWrap: {
    marginBottom: Spacing.two,
  },
  avatar: {
    width: 76,
    height: 76,
    borderRadius: Radius.full,
    backgroundColor: AppColors.surfaceMuted,
  },
  avatarFallback: {
    backgroundColor: AppColors.brand,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarBadge: {
    position: 'absolute',
    right: -2,
    bottom: -2,
    width: 28,
    height: 28,
    borderRadius: Radius.full,
    backgroundColor: AppColors.brand,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: AppColors.surface,
  },
  avatarText: {
    color: AppColors.onBrand,
    fontSize: 26,
    fontWeight: '800',
  },
  name: {
    fontSize: 20,
    fontWeight: '700',
    color: AppColors.text,
  },
  email: {
    fontSize: 14,
    color: AppColors.textMuted,
  },
  badges: {
    flexDirection: 'row',
    gap: Spacing.two,
    marginTop: Spacing.two,
  },
  section: {
    gap: Spacing.two,
  },
  infoCard: {
    paddingHorizontal: Spacing.four,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.three,
    paddingVertical: Spacing.three,
  },
  infoIcon: {
    width: 36,
    height: 36,
    borderRadius: Radius.full,
    backgroundColor: Brand[50],
    alignItems: 'center',
    justifyContent: 'center',
  },
  infoText: {
    flex: 1,
    gap: 2,
  },
  infoLabel: {
    fontSize: 12,
    color: AppColors.textFaint,
    fontWeight: '600',
  },
  infoValue: {
    fontSize: 15,
    color: AppColors.text,
  },
  pressed: {
    opacity: 0.6,
  },
  langRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: Spacing.three,
  },
  langLabel: {
    fontSize: 15,
    fontWeight: '600',
    color: AppColors.text,
  },
  langRadio: {
    width: 24,
    height: 24,
    borderRadius: Radius.full,
    borderWidth: 2,
    borderColor: AppColors.borderStrong,
  },
  locationCard: {
    gap: Spacing.three,
  },
  locationStatus: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
  },
  locationDot: {
    width: 10,
    height: 10,
    borderRadius: Radius.full,
  },
  locationState: {
    fontSize: 15,
    fontWeight: '700',
    color: AppColors.text,
  },
  locationText: {
    fontSize: 13,
    color: AppColors.textMuted,
    lineHeight: 19,
  },
  locationWarning: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: Spacing.two,
    padding: Spacing.three,
    borderRadius: Radius.md,
    backgroundColor: AppColors.warningSoft,
  },
  locationWarningText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
    color: '#a16207',
  },
  locationProof: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
    backgroundColor: AppColors.surfaceMuted,
    borderRadius: Radius.md,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
  },
  locationProofText: {
    flex: 1,
    fontSize: 12,
    color: AppColors.textMuted,
    fontVariant: ['tabular-nums'],
  },
  signOut: {
    marginTop: Spacing.two,
  },
});
