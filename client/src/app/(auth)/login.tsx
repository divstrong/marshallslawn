import { Image } from 'expo-image';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import { Button, Card, TextField } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useLanguage } from '@/context/language';
import { useContentWidth } from '@/hooks/use-layout';
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

const CODE_LENGTH = 6;
/** Seconds before "Resend code" becomes tappable again. */
const RESEND_COOLDOWN = 30;

/**
 * Passwordless sign-in. Step one collects the email and asks the server for a
 * one-time code; step two exchanges that code for a session. There is no
 * password anywhere — and no separate "forgot password" path to get stuck in,
 * because every sign-in is the same flow.
 */
export default function LoginScreen() {
  const { requestCode, signInWithCode, signInWithRole } = useAuth();
  const { t } = useLanguage();
  const insets = useSafeAreaInsets();
  // A sign-in form gains nothing from an iPad's full width.
  const contentWidth = useContentWidth(460);

  const [step, setStep] = useState<'email' | 'code'>('email');
  const [email, setEmail] = useState('');
  const [code, setCode] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);
  const [cooldown, setCooldown] = useState(0);

  const codeInput = useRef<TextInput>(null);

  // Tick the resend cooldown down to zero.
  useEffect(() => {
    if (cooldown <= 0) {
      return;
    }
    const timer = setTimeout(() => setCooldown((value) => value - 1), 1000);
    return () => clearTimeout(timer);
  }, [cooldown]);

  const sendCode = async (resend = false) => {
    const address = email.trim();
    if (!address) {
      setError(t('login.errEmailRequired'));
      return;
    }

    setError(null);
    setBusy(resend ? 'resend' : 'send');
    try {
      await requestCode(address);
      setStep('code');
      setCode('');
      setCooldown(RESEND_COOLDOWN);
      // Land the caret in the code field so the keyboard stays up.
      setTimeout(() => codeInput.current?.focus(), 50);
    } catch (e) {
      setError(e instanceof Error ? e.message : t('login.errFailed'));
    } finally {
      setBusy(null);
    }
  };

  const submitCode = async (value: string) => {
    if (value.length !== CODE_LENGTH) {
      setError(t('login.errCodeLength', { n: CODE_LENGTH }));
      return;
    }

    setError(null);
    setBusy('verify');
    try {
      await signInWithCode(email.trim(), value);
      // On success the root navigator swaps to the app group.
    } catch (e) {
      setError(e instanceof Error ? e.message : t('login.errFailed'));
      setCode('');
      setBusy(null);
      codeInput.current?.focus();
    }
  };

  const onCodeChange = (text: string) => {
    const digits = text.replace(/[^0-9]/g, '').slice(0, CODE_LENGTH);
    setCode(digits);
    // Submit as soon as the last digit lands — including when iOS autofills
    // the whole code from the email in one go.
    if (digits.length === CODE_LENGTH && busy === null) {
      submitCode(digits);
    }
  };

  const editEmail = () => {
    setStep('email');
    setCode('');
    setError(null);
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
          contentWidth,
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

          {step === 'email' ? (
            <>
              <Text style={styles.stepHint}>{t('login.emailHint')}</Text>
              <TextField
                label={t('login.email')}
                value={email}
                onChangeText={setEmail}
                placeholder={t('login.emailPlaceholder')}
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="email-address"
                inputMode="email"
                textContentType="emailAddress"
                autoComplete="email"
                returnKeyType="send"
                onSubmitEditing={() => sendCode()}
              />
              <Button
                label={t('login.sendCode')}
                icon="mail-outline"
                onPress={() => sendCode()}
                loading={busy === 'send'}
                disabled={busy !== null && busy !== 'send'}
              />
            </>
          ) : (
            <>
              <Text style={styles.stepHint}>{t('login.codeHint', { email: email.trim() })}</Text>
              <TextInput
                ref={codeInput}
                style={styles.codeInput}
                value={code}
                onChangeText={onCodeChange}
                placeholder={'—'.repeat(CODE_LENGTH)}
                placeholderTextColor={AppColors.textFaint}
                keyboardType="number-pad"
                inputMode="numeric"
                maxLength={CODE_LENGTH}
                // Lets iOS offer the code straight from the email notification.
                textContentType="oneTimeCode"
                autoComplete="one-time-code"
                autoFocus
                editable={busy !== 'verify'}
              />
              <Button
                label={t('login.verify')}
                icon="log-in-outline"
                onPress={() => submitCode(code)}
                loading={busy === 'verify'}
                disabled={code.length !== CODE_LENGTH || busy !== null}
              />

              <View style={styles.codeActions}>
                <Pressable onPress={editEmail} disabled={busy !== null} hitSlop={8}>
                  <Text style={styles.linkText}>{t('login.changeEmail')}</Text>
                </Pressable>
                <Pressable
                  onPress={() => sendCode(true)}
                  disabled={cooldown > 0 || busy !== null}
                  hitSlop={8}
                >
                  <Text style={[styles.linkText, cooldown > 0 && styles.linkTextMuted]}>
                    {cooldown > 0 ? t('login.resendIn', { n: cooldown }) : t('login.resend')}
                  </Text>
                </Pressable>
              </View>
            </>
          )}
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
    marginBottom: Spacing.three,
  },
  stepHint: {
    fontSize: 14,
    lineHeight: 20,
    color: AppColors.textMuted,
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
  codeInput: {
    backgroundColor: AppColors.surfaceMuted,
    borderWidth: 1,
    borderColor: AppColors.borderStrong,
    borderRadius: Radius.md,
    paddingVertical: Spacing.three,
    marginBottom: Spacing.three,
    fontSize: 32,
    fontWeight: '700',
    letterSpacing: 12,
    textAlign: 'center',
    color: AppColors.text,
    fontVariant: ['tabular-nums'],
  },
  codeActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: Spacing.four,
  },
  linkText: {
    fontSize: 14,
    fontWeight: '600',
    color: AppColors.brand,
  },
  linkTextMuted: {
    color: AppColors.textFaint,
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
