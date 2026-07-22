/**
 * Design tokens for the Marshall's Lawn field app.
 *
 * The app mirrors the server's mobile UI, which is a fixed light theme
 * built on the brand red (#e00a35). `AppColors` / `Brand` / `StatusTone`
 * below drive every app screen; the original `Colors` map is kept for the
 * starter components that still reference it.
 */

import '@/global.css';

import { Platform } from 'react-native';

export const Colors = {
  light: {
    text: '#000000',
    background: '#ffffff',
    backgroundElement: '#F0F0F3',
    backgroundSelected: '#E0E1E6',
    textSecondary: '#60646C',
  },
  dark: {
    text: '#ffffff',
    background: '#000000',
    backgroundElement: '#212225',
    backgroundSelected: '#2E3135',
    textSecondary: '#B0B4BA',
  },
} as const;

export type ThemeColor = keyof typeof Colors.light & keyof typeof Colors.dark;

/** Brand red palette — matches the Tailwind `brand` scale in the server. */
export const Brand = {
  50: '#fef1f3',
  100: '#fde4e8',
  200: '#fbc8d1',
  300: '#f8a0af',
  400: '#f4657f',
  500: '#e00a35',
  600: '#c9092f',
  700: '#a80828',
  800: '#8b0721',
  900: '#73061c',
  950: '#40020e',
} as const;

/** Flat light-theme palette used by every app screen. */
export const AppColors = {
  background: '#f3f4f6',
  surface: '#ffffff',
  surfaceMuted: '#f9fafb',
  border: '#e5e7eb',
  borderStrong: '#d1d5db',

  text: '#111827',
  textSecondary: '#4b5563',
  textMuted: '#6b7280',
  textFaint: '#9ca3af',

  brand: Brand[500],
  brandDark: Brand[600],
  brandSoft: Brand[50],
  brandSoftText: Brand[700],
  onBrand: '#ffffff',

  success: '#16a34a',
  successSoft: '#dcfce7',
  warning: '#ca8a04',
  warningSoft: '#fef9c3',
  danger: '#dc2626',
  dangerSoft: '#fee2e2',
  info: '#2563eb',
  infoSoft: '#dbeafe',
} as const;

export type Tone = { bg: string; fg: string; label: string };

const STATUS_TONES: Record<string, Tone> = {
  // Job statuses
  scheduled: { bg: AppColors.infoSoft, fg: '#1d4ed8', label: 'Scheduled' },
  in_progress: { bg: AppColors.warningSoft, fg: '#a16207', label: 'In Progress' },
  completed: { bg: AppColors.successSoft, fg: '#15803d', label: 'Completed' },
  cancelled: { bg: '#f3f4f6', fg: '#6b7280', label: 'Cancelled' },
  pending: { bg: '#f3f4f6', fg: '#6b7280', label: 'Pending' },
  // Quote statuses
  draft: { bg: '#f3f4f6', fg: '#6b7280', label: 'Draft' },
  sent: { bg: AppColors.infoSoft, fg: '#1d4ed8', label: 'Sent' },
  accepted: { bg: AppColors.successSoft, fg: '#15803d', label: 'Accepted' },
  declined: { bg: AppColors.dangerSoft, fg: '#b91c1c', label: 'Declined' },
  expired: { bg: AppColors.warningSoft, fg: '#a16207', label: 'Expired' },
};

/** Resolve a badge tone for a job or quote status string. */
export function statusTone(status: string | null | undefined): Tone {
  if (status && STATUS_TONES[status]) {
    return STATUS_TONES[status];
  }
  return { bg: '#f3f4f6', fg: '#6b7280', label: status ? formatStatus(status) : '—' };
}

/** "in_progress" -> "In Progress" */
export function formatStatus(value: string): string {
  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

export const Fonts = Platform.select({
  ios: {
    sans: 'system-ui',
    serif: 'ui-serif',
    rounded: 'ui-rounded',
    mono: 'ui-monospace',
  },
  default: {
    sans: 'normal',
    serif: 'serif',
    rounded: 'normal',
    mono: 'monospace',
  },
  web: {
    sans: 'var(--font-display)',
    serif: 'var(--font-serif)',
    rounded: 'var(--font-rounded)',
    mono: 'var(--font-mono)',
  },
});

export const Spacing = {
  half: 2,
  one: 4,
  two: 8,
  three: 12,
  four: 16,
  five: 24,
  six: 32,
  eight: 48,
} as const;

export const Radius = {
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  full: 999,
} as const;

export const BottomTabInset = Platform.select({ ios: 50, android: 80 }) ?? 0;

/** Reading width for detail/form screens — job detail, settings, quote editor. */
export const MaxContentWidth = 800;
/** Wider cap for card grids, which tolerate more columns before looking sparse. */
export const MaxListWidth = 1180;
/** Width of the tablet side navigation rail, per breakpoint. */
export const RailWidth = { medium: 92, expanded: 232 } as const;
