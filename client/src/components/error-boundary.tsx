/**
 * Catches render errors anywhere below it and shows a recoverable screen
 * instead of letting the app die.
 *
 * In a release build an unhandled JavaScript error is fatal — the app closes
 * with no message, which is exactly what a crew member describes as "it
 * crashed". This turns that into a screen they can read us a support code
 * from, and reports the error with that same code attached so the office can
 * find it in Sentry immediately.
 */

import * as Sentry from '@sentry/react-native';
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Button } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { monitoringEnabled } from '@/lib/monitoring';

interface Props {
  children: ReactNode;
}

interface State {
  error: Error | null;
  /** Sentry's id for the report — read aloud to the office to find it. */
  eventId: string | null;
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null, eventId: null };

  static getDerivedStateFromError(error: Error): Partial<State> {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    if (!monitoringEnabled) {
      // Nothing to report to, but keep it visible in a dev console.
      console.error('[Marshalls Lawn] Unhandled render error:', error, info);
      return;
    }

    const eventId = Sentry.captureException(error, {
      contexts: { react: { componentStack: info.componentStack } },
    });
    this.setState({ eventId });
  }

  private reset = () => {
    this.setState({ error: null, eventId: null });
  };

  render() {
    const { error, eventId } = this.state;

    if (!error) {
      return this.props.children;
    }

    return (
      <View style={styles.container}>
        <Icon name="alert-circle" size={48} color={AppColors.danger} />
        <Text style={styles.title}>Something went wrong</Text>
        <Text style={styles.body}>
          The app hit an unexpected problem. Tap below to get back to work — the
          office has already been notified.
        </Text>

        {eventId ? (
          <View style={styles.codeBox}>
            <Text style={styles.codeLabel}>SUPPORT CODE</Text>
            {/* Short enough to read over the radio, unique enough to find. */}
            <Text style={styles.code}>{eventId.slice(0, 8).toUpperCase()}</Text>
          </View>
        ) : null}

        <Button label="Try again" icon="refresh" onPress={this.reset} />
      </View>
    );
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: Spacing.six,
    gap: Spacing.three,
    backgroundColor: AppColors.background,
  },
  title: {
    fontSize: 20,
    fontWeight: '700',
    color: AppColors.text,
  },
  body: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
    color: AppColors.textMuted,
  },
  codeBox: {
    alignItems: 'center',
    gap: Spacing.one,
    backgroundColor: AppColors.surfaceMuted,
    borderRadius: Radius.md,
    paddingVertical: Spacing.three,
    paddingHorizontal: Spacing.six,
  },
  codeLabel: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.6,
    color: AppColors.textFaint,
  },
  code: {
    fontSize: 22,
    fontWeight: '700',
    letterSpacing: 2,
    color: AppColors.text,
    fontVariant: ['tabular-nums'],
  },
});
