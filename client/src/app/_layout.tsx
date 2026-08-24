import { Stack, useNavigationContainerRef } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect } from 'react';

import { ErrorBoundary } from '@/components/error-boundary';
import { AuthProvider, useAuth } from '@/context/auth';
import { ChatProvider } from '@/context/chat';
import { LanguageProvider } from '@/context/language';
import { LocationProvider } from '@/context/location';
import { initMonitoring, navigationIntegration, withMonitoring } from '@/lib/monitoring';
import '@/lib/location-task'; // registers the background location task

// Before anything else renders, so a crash while the tree is still mounting
// is still reported. No-ops until EXPO_PUBLIC_SENTRY_DSN is set.
initMonitoring();

SplashScreen.preventAutoHideAsync();

/**
 * Gates the app behind authentication. `Stack.Protected` swaps between the
 * `(auth)` and `(app)` groups based on the session; the native splash
 * stays up until the persisted token has been checked.
 */
function RootNavigator() {
  const { status } = useAuth();

  useEffect(() => {
    if (status !== 'loading') {
      SplashScreen.hideAsync();
    }
  }, [status]);

  const authenticated = status === 'authenticated';

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Protected guard={authenticated}>
        <Stack.Screen name="(app)" />
      </Stack.Protected>
      <Stack.Protected guard={!authenticated}>
        <Stack.Screen name="(auth)" />
      </Stack.Protected>
    </Stack>
  );
}

function RootLayout() {
  // Feeds screen changes to Sentry as breadcrumbs, so a crash report shows
  // which screens the crew member passed through on the way to it.
  const navigationRef = useNavigationContainerRef();

  useEffect(() => {
    if (navigationRef) {
      navigationIntegration.registerNavigationContainer(navigationRef);
    }
  }, [navigationRef]);

  return (
    <ErrorBoundary>
      <LanguageProvider>
        <AuthProvider>
          <LocationProvider>
            <ChatProvider>
              <StatusBar style="light" />
              <RootNavigator />
            </ChatProvider>
          </LocationProvider>
        </AuthProvider>
      </LanguageProvider>
    </ErrorBoundary>
  );
}

// `Sentry.wrap` adds the native error handlers and touch/profiling hooks that
// have to sit at the very root of the tree.
export default withMonitoring(RootLayout);
