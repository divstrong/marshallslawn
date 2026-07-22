/**
 * Shared UI primitives for the field app. Everything is a fixed light
 * theme built on the brand red, matching the server's mobile views.
 */

import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  type TextInputProps,
  View,
  type ViewStyle,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon, type IconName } from '@/components/icon';
import { AppColors, Radius, Spacing, statusTone } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { useLayout } from '@/hooks/use-layout';

/* -------------------------------------------------------------------------- */
/* Header                                                                     */
/* -------------------------------------------------------------------------- */

interface ScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
  right?: React.ReactNode;
}

/**
 * Brand-red bar pinned to the top of every screen. The title stays on the
 * leading edge at every width, matching the iPadOS navigation bar, but the
 * bar itself gets more air and larger type on a tablet.
 */
export function ScreenHeader({ title, subtitle, onBack, right }: ScreenHeaderProps) {
  const insets = useSafeAreaInsets();
  const { isTablet, gutter } = useLayout();

  return (
    <View
      style={[
        styles.header,
        {
          paddingTop: insets.top + Spacing.three,
          paddingHorizontal: gutter,
          paddingBottom: isTablet ? Spacing.five : Spacing.four,
        },
      ]}
    >
      <View style={styles.headerRow}>
        {onBack ? (
          <Pressable onPress={onBack} hitSlop={10} style={styles.headerBack}>
            <Icon name="chevron-back" size={26} color={AppColors.onBrand} />
          </Pressable>
        ) : null}
        <View style={styles.headerText}>
          <Text style={[styles.headerTitle, isTablet && styles.headerTitleWide]} numberOfLines={1}>
            {title}
          </Text>
          {subtitle ? (
            <Text
              style={[styles.headerSubtitle, isTablet && styles.headerSubtitleWide]}
              numberOfLines={1}
            >
              {subtitle}
            </Text>
          ) : null}
        </View>
        {right ? <View style={styles.headerRight}>{right}</View> : null}
      </View>
    </View>
  );
}

/** Circular icon button for header actions. */
export function HeaderButton({ icon, onPress }: { icon: IconName; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} hitSlop={8} style={styles.headerButton}>
      <Icon name={icon} size={20} color={AppColors.onBrand} />
    </Pressable>
  );
}

/* -------------------------------------------------------------------------- */
/* Surfaces                                                                   */
/* -------------------------------------------------------------------------- */

interface CardProps {
  children: React.ReactNode;
  onPress?: () => void;
  style?: ViewStyle;
  padded?: boolean;
}

export function Card({ children, onPress, style, padded = true }: CardProps) {
  const content = [styles.card, padded && styles.cardPadded, style];
  if (onPress) {
    return (
      <Pressable onPress={onPress} style={({ pressed }) => [...content, pressed && styles.pressed]}>
        {children}
      </Pressable>
    );
  }
  return <View style={content}>{children}</View>;
}

export function Divider() {
  return <View style={styles.divider} />;
}

export function SectionLabel({ children }: { children: string }) {
  return <Text style={styles.sectionLabel}>{children.toUpperCase()}</Text>;
}

/* -------------------------------------------------------------------------- */
/* Badges                                                                     */
/* -------------------------------------------------------------------------- */

export function Badge({ label, bg, fg }: { label: string; bg: string; fg: string }) {
  return (
    <View style={[styles.badge, { backgroundColor: bg }]}>
      <Text style={[styles.badgeText, { color: fg }]}>{label}</Text>
    </View>
  );
}

/** Badge whose colors are derived from a job/quote status string. */
export function StatusBadge({ status }: { status: string | null | undefined }) {
  const { t } = useLanguage();
  const tone = statusTone(status);
  const label = status ? t(`status.${status}`, undefined, tone.label) : tone.label;
  return <Badge label={label} bg={tone.bg} fg={tone.fg} />;
}

/* -------------------------------------------------------------------------- */
/* Buttons                                                                    */
/* -------------------------------------------------------------------------- */

type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'success' | 'ghost';

interface ButtonProps {
  label: string;
  onPress: () => void;
  variant?: ButtonVariant;
  icon?: IconName;
  loading?: boolean;
  disabled?: boolean;
  style?: ViewStyle;
}

const BUTTON_BG: Record<ButtonVariant, string> = {
  primary: AppColors.brand,
  secondary: AppColors.surface,
  danger: AppColors.danger,
  success: AppColors.success,
  ghost: 'transparent',
};

const BUTTON_FG: Record<ButtonVariant, string> = {
  primary: AppColors.onBrand,
  secondary: AppColors.text,
  danger: AppColors.onBrand,
  success: AppColors.onBrand,
  ghost: AppColors.brand,
};

export function Button({
  label,
  onPress,
  variant = 'primary',
  icon,
  loading = false,
  disabled = false,
  style,
}: ButtonProps) {
  const inactive = disabled || loading;
  const fg = BUTTON_FG[variant];

  return (
    <Pressable
      onPress={onPress}
      disabled={inactive}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: BUTTON_BG[variant] },
        variant === 'secondary' && styles.buttonBordered,
        inactive && styles.buttonDisabled,
        pressed && !inactive && styles.pressed,
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator color={fg} size="small" />
      ) : (
        <>
          {icon ? <Icon name={icon} size={18} color={fg} /> : null}
          <Text style={[styles.buttonText, { color: fg }]}>{label}</Text>
        </>
      )}
    </Pressable>
  );
}

/* -------------------------------------------------------------------------- */
/* Confirm modal                                                              */
/* -------------------------------------------------------------------------- */

interface ConfirmModalProps {
  visible: boolean;
  title: string;
  message?: string;
  confirmLabel: string;
  cancelLabel: string;
  confirmVariant?: ButtonVariant;
  confirmIcon?: IconName;
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

/** Themed replacement for Alert.alert confirmations. */
export function ConfirmModal({
  visible,
  title,
  message,
  confirmLabel,
  cancelLabel,
  confirmVariant = 'danger',
  confirmIcon,
  loading = false,
  onConfirm,
  onCancel,
}: ConfirmModalProps) {
  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      statusBarTranslucent
      onRequestClose={loading ? undefined : onCancel}
    >
      <Pressable style={styles.confirmBackdrop} onPress={loading ? undefined : onCancel}>
        {/* Inner press is swallowed so taps on the card don't dismiss. */}
        <Pressable style={styles.confirmCard} onPress={() => {}}>
          <Text style={styles.confirmTitle}>{title}</Text>
          {message ? <Text style={styles.confirmMessage}>{message}</Text> : null}
          <View style={styles.confirmActions}>
            <Button
              label={cancelLabel}
              variant="secondary"
              onPress={onCancel}
              disabled={loading}
              style={styles.confirmButton}
            />
            <Button
              label={confirmLabel}
              variant={confirmVariant}
              icon={confirmIcon}
              loading={loading}
              onPress={onConfirm}
              style={styles.confirmButton}
            />
          </View>
        </Pressable>
      </Pressable>
    </Modal>
  );
}

/* -------------------------------------------------------------------------- */
/* Filter tabs                                                                */
/* -------------------------------------------------------------------------- */

interface FilterOption {
  label: string;
  value: string;
}

interface FilterTabsProps {
  options: FilterOption[];
  value: string;
  onChange: (value: string) => void;
}

export function FilterTabs({ options, value, onChange }: FilterTabsProps) {
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.filterRow}
    >
      {options.map((option) => {
        const active = option.value === value;
        return (
          <Pressable
            key={option.value}
            onPress={() => onChange(option.value)}
            style={[styles.filterPill, active && styles.filterPillActive]}
          >
            <Text style={[styles.filterText, active && styles.filterTextActive]}>
              {option.label}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

/* -------------------------------------------------------------------------- */
/* Text input                                                                 */
/* -------------------------------------------------------------------------- */

interface TextFieldProps extends TextInputProps {
  label?: string;
  error?: string;
  hint?: string;
}

export function TextField({ label, error, hint, style, ...rest }: TextFieldProps) {
  return (
    <View style={styles.field}>
      {label ? <Text style={styles.fieldLabel}>{label}</Text> : null}
      <TextInput
        placeholderTextColor={AppColors.textFaint}
        style={[styles.input, error ? styles.inputError : null, style]}
        {...rest}
      />
      {error ? (
        <Text style={styles.fieldError}>{error}</Text>
      ) : hint ? (
        <Text style={styles.fieldHint}>{hint}</Text>
      ) : null}
    </View>
  );
}

/* -------------------------------------------------------------------------- */
/* State views                                                                */
/* -------------------------------------------------------------------------- */

export function LoadingState({ label }: { label?: string }) {
  return (
    <View style={styles.centered}>
      <ActivityIndicator color={AppColors.brand} size="large" />
      {label ? <Text style={styles.stateText}>{label}</Text> : null}
    </View>
  );
}

interface EmptyStateProps {
  icon: IconName;
  title: string;
  message?: string;
}

export function EmptyState({ icon, title, message }: EmptyStateProps) {
  return (
    <View style={styles.centered}>
      <View style={styles.emptyIcon}>
        <Icon name={icon} size={30} color={AppColors.textFaint} />
      </View>
      <Text style={styles.stateTitle}>{title}</Text>
      {message ? <Text style={styles.stateText}>{message}</Text> : null}
    </View>
  );
}

export function ErrorState({ message, onRetry }: { message: string; onRetry?: () => void }) {
  const { t } = useLanguage();
  return (
    <View style={styles.centered}>
      <View style={[styles.emptyIcon, { backgroundColor: AppColors.dangerSoft }]}>
        <Icon name="alert-circle-outline" size={30} color={AppColors.danger} />
      </View>
      <Text style={styles.stateTitle}>{t('common.somethingWrong')}</Text>
      <Text style={styles.stateText}>{message}</Text>
      {onRetry ? (
        <Button
          label={t('common.retry')}
          variant="secondary"
          onPress={onRetry}
          style={styles.retry}
        />
      ) : null}
    </View>
  );
}

/* -------------------------------------------------------------------------- */
/* Styles                                                                     */
/* -------------------------------------------------------------------------- */

const styles = StyleSheet.create({
  header: {
    backgroundColor: AppColors.brand,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
  },
  headerBack: {
    marginLeft: -Spacing.one,
  },
  headerText: {
    flex: 1,
  },
  headerRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
  },
  headerTitle: {
    color: AppColors.onBrand,
    fontSize: 22,
    fontWeight: '700',
  },
  headerTitleWide: {
    fontSize: 28,
  },
  headerSubtitle: {
    color: 'rgba(255,255,255,0.85)',
    fontSize: 13,
    marginTop: 2,
  },
  headerSubtitleWide: {
    fontSize: 15,
    marginTop: 3,
  },
  headerButton: {
    width: 38,
    height: 38,
    borderRadius: Radius.full,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  card: {
    backgroundColor: AppColors.surface,
    borderRadius: Radius.lg,
    borderWidth: 1,
    borderColor: AppColors.border,
  },
  cardPadded: {
    padding: Spacing.four,
  },
  pressed: {
    opacity: 0.7,
  },
  divider: {
    height: 1,
    backgroundColor: AppColors.border,
    marginVertical: Spacing.three,
  },
  sectionLabel: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.6,
    color: AppColors.textFaint,
    marginBottom: Spacing.two,
  },
  badge: {
    paddingHorizontal: Spacing.three,
    paddingVertical: 4,
    borderRadius: Radius.full,
    alignSelf: 'flex-start',
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '600',
  },
  button: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.two,
    height: 50,
    borderRadius: Radius.md,
    paddingHorizontal: Spacing.four,
  },
  buttonBordered: {
    borderWidth: 1,
    borderColor: AppColors.borderStrong,
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonText: {
    fontSize: 15,
    fontWeight: '700',
  },
  filterRow: {
    gap: Spacing.two,
    paddingVertical: Spacing.one,
  },
  filterPill: {
    paddingHorizontal: Spacing.four,
    paddingVertical: Spacing.two,
    borderRadius: Radius.full,
    backgroundColor: AppColors.surface,
    borderWidth: 1,
    borderColor: AppColors.border,
  },
  filterPillActive: {
    backgroundColor: AppColors.brand,
    borderColor: AppColors.brand,
  },
  filterText: {
    fontSize: 13,
    fontWeight: '600',
    color: AppColors.textMuted,
  },
  filterTextActive: {
    color: AppColors.onBrand,
  },
  field: {
    marginBottom: Spacing.three,
  },
  fieldLabel: {
    fontSize: 13,
    fontWeight: '600',
    color: AppColors.textSecondary,
    marginBottom: Spacing.two,
  },
  input: {
    backgroundColor: AppColors.surface,
    borderWidth: 1,
    borderColor: AppColors.borderStrong,
    borderRadius: Radius.md,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.three,
    fontSize: 15,
    color: AppColors.text,
  },
  inputError: {
    borderColor: AppColors.danger,
  },
  fieldError: {
    fontSize: 12,
    color: AppColors.danger,
    marginTop: Spacing.one,
  },
  fieldHint: {
    fontSize: 12,
    color: AppColors.textFaint,
    marginTop: Spacing.one,
  },
  centered: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: Spacing.eight,
    paddingHorizontal: Spacing.five,
    gap: Spacing.two,
  },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: Radius.full,
    backgroundColor: AppColors.border,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.one,
  },
  stateTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: AppColors.text,
  },
  stateText: {
    fontSize: 14,
    color: AppColors.textMuted,
    textAlign: 'center',
  },
  retry: {
    marginTop: Spacing.three,
    paddingHorizontal: Spacing.five,
  },
  confirmBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: Spacing.five,
  },
  confirmCard: {
    width: '100%',
    maxWidth: 380,
    backgroundColor: AppColors.surface,
    borderRadius: Radius.lg,
    padding: Spacing.four,
    gap: Spacing.two,
  },
  confirmTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: AppColors.text,
  },
  confirmMessage: {
    fontSize: 14,
    lineHeight: 20,
    color: AppColors.textSecondary,
  },
  confirmActions: {
    flexDirection: 'row',
    gap: Spacing.three,
    marginTop: Spacing.three,
  },
  confirmButton: {
    flex: 1,
  },
});
