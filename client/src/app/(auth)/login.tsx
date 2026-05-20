import { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import { Button, Card, TextField } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import type { Role } from '@/lib/types';

const DEV_ROLES: { role: Role; label: string; icon: 'calendar' | 'briefcase' | 'document-text' }[] = [
  { role: 'foreman', label: 'Foreman', icon: 'calendar' },
  { role: 'field', label: 'Field', icon: 'briefcase' },
  { role: 'estimator', label: 'Estimator', icon: 'document-text' },
];

export default function LoginScreen() {
  const { signIn, signInWithRole } = useAuth();
  const insets = useSafeAreaInsets();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);

  const submit = async () => {
    if (!email.trim() || !password) {
      setError('Enter your email and password.');
      return;
    }
    setError(null);
    setBusy('login');
    try {
      await signIn(email.trim(), password);
      // On success the root navigator swaps to the app group.
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Sign in failed.');
      setBusy(null);
    }
  };

  const quickLogin = async (role: Role) => {
    setError(null);
    setBusy(role);
    try {
      await signInWithRole(role);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Sign in failed.');
      setBusy(null);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        style={styles.flex}
        contentContainerStyle={[
          styles.content,
          { paddingTop: insets.top + Spacing.eight, paddingBottom: insets.bottom + Spacing.five },
        ]}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.brandMark}>
          <Icon name="leaf" size={36} color={AppColors.onBrand} />
        </View>
        <Text style={styles.brandName}>Marshall&apos;s Lawn</Text>
        <Text style={styles.brandTagline}>Crew Field App</Text>

        <Card style={styles.card}>
          <Text style={styles.cardTitle}>Sign in</Text>

          {error ? (
            <View style={styles.errorBox}>
              <Icon name="alert-circle" size={16} color={AppColors.danger} />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          ) : null}

          <TextField
            label="Email"
            value={email}
            onChangeText={setEmail}
            placeholder="you@marshallslawn.com"
            autoCapitalize="none"
            autoCorrect={false}
            keyboardType="email-address"
            inputMode="email"
          />
          <TextField
            label="Password"
            value={password}
            onChangeText={setPassword}
            placeholder="Enter your password"
            secureTextEntry
            hint="Demo account: foreman@marshallslawn.test / password"
          />

          <Button
            label="Sign in"
            icon="log-in-outline"
            onPress={submit}
            loading={busy === 'login'}
            disabled={busy !== null && busy !== 'login'}
          />
        </Card>

        {__DEV__ ? (
          <Card style={styles.card}>
            <Text style={styles.devLabel}>DEVELOPER QUICK LOGIN</Text>
            <Text style={styles.devHint}>Jump straight into a role to preview its experience.</Text>
            <View style={styles.devRow}>
              {DEV_ROLES.map((item) => (
                <Pressable
                  key={item.role}
                  onPress={() => quickLogin(item.role)}
                  disabled={busy !== null}
                  style={({ pressed }) => [
                    styles.devButton,
                    pressed && styles.devButtonPressed,
                    busy !== null && busy !== item.role && styles.devButtonMuted,
                  ]}
                >
                  <Icon name={item.icon} size={22} color={AppColors.brand} />
                  <Text style={styles.devButtonText}>{item.label}</Text>
                </Pressable>
              ))}
            </View>
          </Card>
        ) : null}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: {
    flex: 1,
    backgroundColor: AppColors.brand,
  },
  content: {
    paddingHorizontal: Spacing.four,
    alignItems: 'center',
  },
  brandMark: {
    width: 76,
    height: 76,
    borderRadius: Radius.full,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandName: {
    color: AppColors.onBrand,
    fontSize: 26,
    fontWeight: '800',
    marginTop: Spacing.three,
  },
  brandTagline: {
    color: 'rgba(255,255,255,0.85)',
    fontSize: 14,
    marginTop: 2,
    marginBottom: Spacing.six,
  },
  card: {
    width: '100%',
    marginTop: Spacing.four,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: AppColors.text,
    marginBottom: Spacing.four,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
    backgroundColor: AppColors.dangerSoft,
    borderRadius: Radius.md,
    padding: Spacing.three,
    marginBottom: Spacing.three,
  },
  errorText: {
    flex: 1,
    color: AppColors.danger,
    fontSize: 13,
    fontWeight: '500',
  },
  devLabel: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.6,
    color: AppColors.textFaint,
  },
  devHint: {
    fontSize: 13,
    color: AppColors.textMuted,
    marginTop: Spacing.one,
    marginBottom: Spacing.three,
  },
  devRow: {
    flexDirection: 'row',
    gap: Spacing.two,
  },
  devButton: {
    flex: 1,
    alignItems: 'center',
    gap: Spacing.one,
    paddingVertical: Spacing.three,
    borderRadius: Radius.md,
    borderWidth: 1,
    borderColor: AppColors.border,
    backgroundColor: AppColors.surfaceMuted,
  },
  devButtonPressed: {
    opacity: 0.6,
  },
  devButtonMuted: {
    opacity: 0.4,
  },
  devButtonText: {
    fontSize: 13,
    fontWeight: '600',
    color: AppColors.text,
  },
});
