/**
 * Role-driven navigation. Each role gets a different set of tabs and a
 * different landing tab, mirroring the experiences described for the
 * Foreman, Field, and Estimator users.
 */

import { Ionicons } from '@expo/vector-icons';

import type { Role } from '@/lib/types';

export type TabName = 'schedule' | 'jobs' | 'time' | 'quotes' | 'chat' | 'settings';

export interface TabDef {
  name: TabName;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  activeIcon: keyof typeof Ionicons.glyphMap;
}

export const TABS: Record<TabName, TabDef> = {
  schedule: { name: 'schedule', label: 'Schedule', icon: 'calendar-outline', activeIcon: 'calendar' },
  jobs: { name: 'jobs', label: 'Jobs', icon: 'briefcase-outline', activeIcon: 'briefcase' },
  time: { name: 'time', label: 'Time', icon: 'time-outline', activeIcon: 'time' },
  quotes: {
    name: 'quotes',
    label: 'Quotes',
    icon: 'document-text-outline',
    activeIcon: 'document-text',
  },
  chat: { name: 'chat', label: 'Chat', icon: 'chatbubbles-outline', activeIcon: 'chatbubbles' },
  settings: {
    name: 'settings',
    label: 'Profile',
    icon: 'person-circle-outline',
    activeIcon: 'person-circle',
  },
};

/** Tabs shown in the bottom bar, in order, per role. */
/**
 * The employee time clock ("time") is intentionally hidden (issue #9): no employee
 * time is tracked in the app. Foremen log start/stop per job from the job detail
 * screen instead. The screen file is retained in case the clock is revived later.
 */
export const ROLE_TABS: Record<Role, TabName[]> = {
  foreman: ['schedule', 'jobs', 'chat', 'settings'],
  // Spray Tech mirrors the Foreman experience (issue #12).
  spray_tech: ['schedule', 'jobs', 'chat', 'settings'],
  field: ['jobs', 'settings'],
  estimator: ['quotes', 'jobs', 'settings'],
};

/** Landing tab when a role first enters the app. */
export const ROLE_DEFAULT_TAB: Record<Role, TabName> = {
  foreman: 'schedule',
  spray_tech: 'schedule',
  field: 'jobs',
  estimator: 'quotes',
};

export function tabsForRole(role: Role | undefined): TabDef[] {
  const names: TabName[] = role ? ROLE_TABS[role] : ['jobs', 'settings'];
  return names.map((name) => TABS[name]);
}

export function defaultRouteForRole(role: Role | undefined): string {
  const tab = role ? ROLE_DEFAULT_TAB[role] : 'jobs';
  return `/${tab}`;
}
