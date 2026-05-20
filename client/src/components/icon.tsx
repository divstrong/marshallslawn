import { Ionicons } from '@expo/vector-icons';

import { AppColors } from '@/constants/theme';

export type IconName = keyof typeof Ionicons.glyphMap;

interface IconProps {
  name: IconName;
  size?: number;
  color?: string;
}

/** Single wrapper so the whole app draws from one icon set (Ionicons). */
export function Icon({ name, size = 20, color = AppColors.text }: IconProps) {
  return <Ionicons name={name} size={size} color={color} />;
}
