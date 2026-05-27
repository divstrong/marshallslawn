import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import { tabsForRole } from '@/constants/roles';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useChat } from '@/context/chat';
import { useLanguage } from '@/context/language';

/**
 * Bottom navigation. All tab screens exist for every role; this bar only
 * renders — and orders — the tabs that belong to the signed-in role.
 */
export function TabBar({ state, navigation }: BottomTabBarProps) {
  const { employee } = useAuth();
  const { unread } = useChat();
  const { t } = useLanguage();
  const insets = useSafeAreaInsets();

  const tabs = tabsForRole(employee?.role);
  const activeName = state.routes[state.index]?.name;

  return (
    <View style={[styles.bar, { paddingBottom: Math.max(insets.bottom, Spacing.three) }]}>
      {tabs.map((tab) => {
        const route = state.routes.find((r) => r.name === tab.name);
        if (!route) {
          return null;
        }

        const active = activeName === tab.name;
        const color = active ? AppColors.brand : AppColors.textFaint;

        const onPress = () => {
          const event = navigation.emit({
            type: 'tabPress',
            target: route.key,
            canPreventDefault: true,
          });
          if (!active && !event.defaultPrevented) {
            navigation.navigate(route.name as never);
          }
        };

        const showBadge = tab.name === 'chat' && unread > 0;

        return (
          <Pressable key={tab.name} onPress={onPress} style={styles.tab} hitSlop={6}>
            <View>
              <Icon name={active ? tab.activeIcon : tab.icon} size={24} color={color} />
              {showBadge ? (
                <View style={styles.badge}>
                  <Text style={styles.badgeText}>{unread > 9 ? '9+' : unread}</Text>
                </View>
              ) : null}
            </View>
            <Text style={[styles.label, { color }]}>{t(`nav.${tab.name}`)}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    backgroundColor: AppColors.surface,
    borderTopWidth: 1,
    borderTopColor: AppColors.border,
    paddingTop: Spacing.two,
    paddingHorizontal: Spacing.two,
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
    paddingVertical: Spacing.one,
    borderRadius: Radius.md,
  },
  label: {
    fontSize: 11,
    fontWeight: '600',
  },
  badge: {
    position: 'absolute',
    top: -5,
    right: -9,
    minWidth: 17,
    height: 17,
    borderRadius: Radius.full,
    backgroundColor: AppColors.danger,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 3,
  },
  badgeText: {
    color: AppColors.onBrand,
    fontSize: 10,
    fontWeight: '800',
  },
});
