import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { Image } from 'expo-image';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import { tabsForRole, type TabDef } from '@/constants/roles';
import { AppColors, Radius, RailWidth, Spacing } from '@/constants/theme';
import { useAuth } from '@/context/auth';
import { useChat } from '@/context/chat';
import { useLanguage } from '@/context/language';
import { useLayout } from '@/hooks/use-layout';

/**
 * Primary navigation. All tab screens exist for every role; this bar only
 * renders — and orders — the tabs that belong to the signed-in role.
 *
 * On phones it is the bottom bar. On tablets the navigator flips
 * `tabBarPosition` to `left` (see the tabs layout) and this becomes a
 * vertical rail: icon-over-label at iPad-portrait widths, and a full
 * sidebar with the wordmark once there is room for it.
 */
export function TabBar({ state, navigation }: BottomTabBarProps) {
  const { employee } = useAuth();
  const { unread } = useChat();
  const { t } = useLanguage();
  const insets = useSafeAreaInsets();
  const { isTablet, isExpanded } = useLayout();

  const tabs = tabsForRole(employee?.role);
  const activeName = state.routes[state.index]?.name;

  const items = tabs.map((tab) => {
    const route = state.routes.find((r) => r.name === tab.name);
    if (!route) {
      return null;
    }

    const active = activeName === tab.name;

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

    return (
      <TabItem
        key={tab.name}
        tab={tab}
        label={t(`nav.${tab.name}`)}
        active={active}
        badge={tab.name === 'chat' ? unread : 0}
        variant={isExpanded ? 'sidebar' : isTablet ? 'rail' : 'bottom'}
        onPress={onPress}
      />
    );
  });

  if (isTablet) {
    return (
      <View
        style={[
          styles.rail,
          {
            width: isExpanded ? RailWidth.expanded : RailWidth.medium,
            paddingLeft: insets.left,
            paddingTop: insets.top + Spacing.four,
            paddingBottom: Math.max(insets.bottom, Spacing.four),
          },
        ]}
      >
        <Image
          source={require('@/assets/images/logo.png')}
          style={isExpanded ? styles.railLogoWide : styles.railLogo}
          contentFit="contain"
        />
        <View style={styles.railItems}>{items}</View>
      </View>
    );
  }

  return (
    <View style={[styles.bar, { paddingBottom: Math.max(insets.bottom, Spacing.three) }]}>
      {items}
    </View>
  );
}

interface TabItemProps {
  tab: TabDef;
  label: string;
  active: boolean;
  badge: number;
  variant: 'bottom' | 'rail' | 'sidebar';
  onPress: () => void;
}

function TabItem({ tab, label, active, badge, variant, onPress }: TabItemProps) {
  const color = active ? AppColors.brand : AppColors.textFaint;
  const sidebar = variant === 'sidebar';

  return (
    <Pressable
      onPress={onPress}
      hitSlop={6}
      style={[
        styles.tab,
        variant === 'bottom' && styles.tabBottom,
        variant === 'rail' && styles.tabRail,
        sidebar && styles.tabSidebar,
        variant !== 'bottom' && active && styles.tabActive,
      ]}
    >
      <View>
        <Icon name={active ? tab.activeIcon : tab.icon} size={24} color={color} />
        {badge > 0 ? (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{badge > 9 ? '9+' : badge}</Text>
          </View>
        ) : null}
      </View>
      <Text
        numberOfLines={1}
        style={[
          styles.label,
          variant === 'bottom' && styles.labelBottom,
          sidebar && styles.labelSidebar,
          { color },
        ]}
      >
        {label}
      </Text>
    </Pressable>
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
  rail: {
    backgroundColor: AppColors.surface,
    borderRightWidth: 1,
    borderRightColor: AppColors.border,
    paddingHorizontal: Spacing.two,
    gap: Spacing.five,
  },
  railLogo: {
    height: 34,
    width: '100%',
  },
  railLogoWide: {
    height: 46,
    width: '100%',
    alignSelf: 'flex-start',
  },
  railItems: {
    gap: Spacing.one,
  },
  tab: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 3,
    borderRadius: Radius.md,
  },
  tabBottom: {
    flex: 1,
    paddingVertical: Spacing.one,
  },
  tabRail: {
    paddingVertical: Spacing.three,
  },
  tabSidebar: {
    flexDirection: 'row',
    justifyContent: 'flex-start',
    gap: Spacing.three,
    paddingVertical: Spacing.three,
    paddingHorizontal: Spacing.three,
  },
  tabActive: {
    backgroundColor: AppColors.brandSoft,
  },
  label: {
    fontSize: 12,
    fontWeight: '600',
  },
  labelBottom: {
    fontSize: 11,
  },
  labelSidebar: {
    flex: 1,
    fontSize: 15,
    fontWeight: '700',
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
