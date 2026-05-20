import { useState } from 'react';
import { Alert, Linking, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Icon, type IconName } from '@/components/icon';
import { Badge, Button, Card, Divider, ScreenHeader, SectionLabel } from '@/components/ui';
import { APP_VERSION } from '@/constants/config';
import { AppColors, Brand, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLocationTracking } from '@/context/location';
import { openMaps, openPhone } from '@/lib/links';

export default function SettingsScreen() {
  const { employee, signOut } = useAuth();
  const tracking = useLocationTracking();
  const [signingOut, setSigningOut] = useState(false);

  if (!employee) {
    return null;
  }

  const initials = `${employee.first_name?.[0] ?? ''}${employee.last_name?.[0] ?? ''}`.toUpperCase();
  const address = [employee.address, [employee.city, employee.state].filter(Boolean).join(', ')]
    .filter(Boolean)
    .join(' · ');

  const confirmSignOut = () => {
    Alert.alert('Sign out', 'Are you sure you want to sign out?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Sign out',
        style: 'destructive',
        onPress: async () => {
          setSigningOut(true);
          await signOut();
        },
      },
    ]);
  };

  return (
    <View style={styles.screen}>
      <ScreenHeader title="Profile" subtitle="Account & settings" />

      <ScrollView contentContainerStyle={styles.body}>
        {/* Profile */}
        <Card style={styles.profileCard}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{initials || '?'}</Text>
          </View>
          <Text style={styles.name}>{employee.name}</Text>
          {employee.email ? <Text style={styles.email}>{employee.email}</Text> : null}
          <View style={styles.badges}>
            <Badge label={employee.role_label} bg={Brand[50]} fg={Brand[700]} />
            {employee.division ? (
              <Badge label={employee.division} bg={AppColors.border} fg={AppColors.textSecondary} />
            ) : null}
          </View>
        </Card>

        {/* Contact */}
        <View style={styles.section}>
          <SectionLabel>Contact Info</SectionLabel>
          <Card padded={false} style={styles.infoCard}>
            <InfoRow
              icon="call-outline"
              label="Phone"
              value={employee.phone ?? 'Not on file'}
              onPress={employee.phone ? () => openPhone(employee.phone!) : undefined}
            />
            <Divider />
            <InfoRow icon="mail-outline" label="Email" value={employee.email ?? 'Not on file'} />
            {address ? (
              <>
                <Divider />
                <InfoRow
                  icon="location-outline"
                  label="Address"
                  value={address}
                  onPress={() => openMaps(address)}
                />
              </>
            ) : null}
          </Card>
        </View>

        {/* Location sharing (foreman only) */}
        {tracking.tracksThisRole ? (
          <View style={styles.section}>
            <SectionLabel>Location Sharing</SectionLabel>
            <Card style={styles.locationCard}>
              <View style={styles.locationStatus}>
                <View
                  style={[
                    styles.locationDot,
                    { backgroundColor: tracking.active ? AppColors.success : AppColors.warning },
                  ]}
                />
                <Text style={styles.locationState}>
                  {tracking.active ? 'On — sharing with dispatch' : 'Off'}
                </Text>
              </View>
              <Text style={styles.locationText}>
                {tracking.active
                  ? 'Your location is shared with the office while you are on the clock so dispatch can find your crew on the map.'
                  : !tracking.supported
                    ? 'Location tracking runs on the Marshall’s Lawn mobile app (iOS / Android).'
                    : tracking.permission === 'denied'
                      ? 'Location permission is turned off. Turn it on in device settings so dispatch can see your crew.'
                      : 'Turn on location sharing so dispatch can see where your crew is during the workday.'}
              </Text>
              {tracking.supported && !tracking.active ? (
                tracking.permission === 'denied' ? (
                  <Button
                    label="Open device settings"
                    variant="secondary"
                    icon="settings-outline"
                    onPress={() => Linking.openSettings()}
                  />
                ) : (
                  <Button
                    label="Enable location sharing"
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
          <SectionLabel>About</SectionLabel>
          <Card padded={false} style={styles.infoCard}>
            <InfoRow icon="phone-portrait-outline" label="App version" value={APP_VERSION} />
          </Card>
        </View>

        <Button
          label="Sign out"
          icon="log-out-outline"
          variant="danger"
          loading={signingOut}
          onPress={confirmSignOut}
          style={styles.signOut}
        />
      </ScrollView>
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
      <Pressable onPress={onPress} style={({ pressed }) => pressed && styles.pressed}>
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
    padding: Spacing.four,
    gap: Spacing.three,
  },
  profileCard: {
    alignItems: 'center',
    gap: Spacing.one,
    paddingVertical: Spacing.five,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: Radius.full,
    backgroundColor: AppColors.brand,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.two,
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
  signOut: {
    marginTop: Spacing.two,
  },
});
