import { Tabs } from 'expo-router';

import { TabBar } from '@/components/tab-bar';
import { useLayout } from '@/hooks/use-layout';

/**
 * Tab navigator. Every role shares the same screen files; the custom
 * `TabBar` renders only the tabs that belong to the signed-in role.
 *
 * Tablets move the bar to the leading edge, which is where iPadOS users
 * expect primary navigation to live and stops the tabs from being stranded
 * in the middle of a 1366pt-wide bottom bar.
 */
export default function TabsLayout() {
  const { isTablet } = useLayout();

  return (
    <Tabs
      screenOptions={{ headerShown: false, tabBarPosition: isTablet ? 'left' : 'bottom' }}
      tabBar={(props) => <TabBar {...props} />}
    >
      <Tabs.Screen name="index" options={{ title: 'Home' }} />
      <Tabs.Screen name="schedule" options={{ title: 'Schedule' }} />
      <Tabs.Screen name="jobs" options={{ title: 'Jobs' }} />
      <Tabs.Screen name="time" options={{ title: 'Time' }} />
      <Tabs.Screen name="quotes" options={{ title: 'Quotes' }} />
      <Tabs.Screen name="chat" options={{ title: 'Chat' }} />
      <Tabs.Screen name="settings" options={{ title: 'Profile' }} />
    </Tabs>
  );
}
