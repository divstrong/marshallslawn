/**
 * Responsive layout primitives.
 *
 * Everything keys off the *window* width rather than the device model, so
 * an iPad in Slide Over or a half-width Split View correctly falls back to
 * the phone layout, and the app reflows live as the user drags the divider.
 */

import { useWindowDimensions, type ViewStyle } from 'react-native';

import { MaxContentWidth, RailWidth, Spacing } from '@/constants/theme';

/** Width, in dp, at which the layout stops being a phone layout. */
const MEDIUM_WIDTH = 700;
/** Width, in dp, at which there is room for side-by-side content. */
const EXPANDED_WIDTH = 1024;
/** Narrowest a job/quote card may get before a column is dropped. */
const MIN_CARD_WIDTH = 300;

export type Breakpoint = 'compact' | 'medium' | 'expanded';

export interface Layout {
  width: number;
  height: number;
  breakpoint: Breakpoint;
  /** Phone, Slide Over, or a narrow Split View pane. */
  isCompact: boolean;
  /** Anything wider than a phone — iPad portrait and up. */
  isTablet: boolean;
  /** iPad landscape / full-screen 13" portrait: room for two columns. */
  isExpanded: boolean;
  isLandscape: boolean;
  /**
   * Column count for a card grid on a *tab* screen. The navigation rail is
   * subtracted first, so this reflects the space the list actually gets.
   */
  columns: number;
  /** Screen edge padding. */
  gutter: number;
}

export function useLayout(): Layout {
  const { width, height } = useWindowDimensions();

  // Keyed off the window, never off the post-rail width: the rail's own size
  // depends on the breakpoint, so feeding that back in would oscillate.
  const breakpoint: Breakpoint =
    width >= EXPANDED_WIDTH ? 'expanded' : width >= MEDIUM_WIDTH ? 'medium' : 'compact';

  const rail =
    breakpoint === 'expanded'
      ? RailWidth.expanded
      : breakpoint === 'medium'
        ? RailWidth.medium
        : 0;

  return {
    width,
    height,
    breakpoint,
    isCompact: breakpoint === 'compact',
    isTablet: breakpoint !== 'compact',
    isExpanded: breakpoint === 'expanded',
    isLandscape: width > height,
    columns: Math.max(1, Math.min(3, Math.floor((width - rail) / MIN_CARD_WIDTH))),
    gutter: breakpoint === 'compact' ? Spacing.four : Spacing.five,
  };
}

/**
 * Centers a screen's content and caps its width once the window grows past
 * it. Returns `null` on phones so the style array stays a no-op there.
 *
 * Safe to spread into a `contentContainerStyle` for both ScrollView and
 * FlatList, or onto a plain View.
 */
export function useContentWidth(max: number = MaxContentWidth): ViewStyle | null {
  const { width } = useWindowDimensions();
  if (width <= max) {
    return null;
  }
  return { width: '100%', maxWidth: max, alignSelf: 'center' };
}

/**
 * Pads a list so the final grid row is complete, keeping `flex: 1` cells the
 * same width as full rows instead of stretching to fill the gap. Returns the
 * original array untouched for single-column layouts.
 */
export function padToColumns<T>(items: T[], columns: number): (T | null)[] {
  if (columns <= 1 || items.length === 0) {
    return items;
  }
  const remainder = items.length % columns;
  if (remainder === 0) {
    return items;
  }
  return [...items, ...Array<null>(columns - remainder).fill(null)];
}
