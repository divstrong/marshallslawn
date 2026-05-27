import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import * as SplashScreen from 'expo-splash-screen';
import { useEffect } from 'react';

import { AuthProvider, useAuth } from '@/context/auth';
import { ChatProvider } from '@/context/chat';
import { LanguageProvider } from '@/context/language';
import { LocationProvider } from '@/context/location';
import '@/lib/location-task'; // registers the background location task

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

export default function RootLayout() {
  return (
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
  );
}
