import { Image } from 'expo-image';
import { StatusBar } from 'expo-status-bar';
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
import { useLanguage } from '@/context/language';
import type { Role } from '@/lib/types';

const DEV_ROLES: {
  role: Role;
  labelKey: string;
  icon: 'calendar' | 'briefcase' | 'document-text' | 'water';
}[] = [
  { role: 'foreman', labelKey: 'login.roleForeman', icon: 'calendar' },
  { role: 'spray_tech', labelKey: 'login.roleSprayTech', icon: 'water' },
  { role: 'field', labelKey: 'login.roleField', icon: 'briefcase' },
  { role: 'estimator', labelKey: 'login.roleEstimator', icon: 'document-text' },
];

export default function LoginScreen() {
  const { signIn, signInWithRole } = useAuth();
  const { t } = useLanguage();
  const insets = useSafeAreaInsets();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);

  const submit = async () => {
    if (!email.trim() || !password) {
      setError(t('login.errCredentials'));
      return;
    }
    setError(null);
    setBusy('login');
    try {
      await signIn(email.trim(), password);
      // On success the root navigator swaps to the app group.
    } catch (e) {
      setError(e instanceof Error ? e.message : t('login.errFailed'));
      setBusy(null);
    }
  };

  const quickLogin = async (role: Role) => {
    setError(null);
    setBusy(role);
    try {
      await signInWithRole(role);
    } catch (e) {
      setError(e instanceof Error ? e.message : t('login.errFailed'));
      setBusy(null);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <StatusBar style="dark" />
      <ScrollView
        style={styles.flex}
        contentContainerStyle={[
          styles.content,
          { paddingTop: insets.top + Spacing.eight, paddingBottom: insets.bottom + Spacing.five },
        ]}
        keyboardShouldPersistTaps="handled"
      >
        <Image
          source={require('@/assets/images/logo.png')}
          style={styles.logo}
          contentFit="contain"
        />
        <Text style={styles.brandTagline}>{t('login.tagline')}</Text>

        <Card style={styles.card}>
          <Text style={styles.cardTitle}>{t('login.signIn')}</Text>

          {error ? (
            <View style={styles.errorBox}>
              <Icon name="alert-circle" size={16} color={AppColors.danger} />
              <Text style={styles.errorText}>{error}</Text>
            </View>
          ) : null}

          <TextField
            label={t('login.email')}
            value={email}
            onChangeText={setEmail}
            placeholder={t('login.emailPlaceholder')}
            autoCapitalize="none"
            autoCorrect={false}
            keyboardType="email-address"
            inputMode="email"
          />
          <TextField
            label={t('login.password')}
            value={password}
            onChangeText={setPassword}
            placeholder={t('login.passwordPlaceholder')}
            secureTextEntry
          />

          <Button
            label={t('login.signIn')}
            icon="log-in-outline"
            onPress={submit}
            loading={busy === 'login'}
            disabled={busy !== null && busy !== 'login'}
          />
        </Card>

        {__DEV__ ? (
          <Card style={styles.card}>
            <Text style={styles.devLabel}>{t('login.devTitle')}</Text>
            <Text style={styles.devHint}>{t('login.devHint')}</Text>
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
                  <Text style={styles.devButtonText}>{t(item.labelKey)}</Text>
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
    backgroundColor: AppColors.background,
  },
  content: {
    paddingHorizontal: Spacing.four,
    alignItems: 'center',
  },
  logo: {
    width: 240,
    height: 150,
  },
  brandTagline: {
    color: AppColors.textMuted,
    fontSize: 14,
    fontWeight: '600',
    marginTop: Spacing.one,
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
