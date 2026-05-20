import { Stack } from 'expo-router';

/**
 * Stack that hosts the tab navigator plus the full-screen detail routes
 * (job detail, quote editor) that push on top of it.
 */
export default function AppLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="job/[id]" />
      <Stack.Screen name="quote/[id]" />
      <Stack.Screen name="quote/new" />
    </Stack>
  );
}
