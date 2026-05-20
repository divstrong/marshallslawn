import { Redirect } from 'expo-router';

import { defaultRouteForRole } from '@/constants/roles';
import { useAuth } from '@/context/auth';

/**
 * Entry point of the tab group — bounces to the landing tab for the
 * signed-in role (Foreman → Schedule, Field → Jobs, Estimator → Quotes).
 */
export default function TabsIndex() {
  const { employee } = useAuth();
  return <Redirect href={defaultRouteForRole(employee?.role)} />;
}
