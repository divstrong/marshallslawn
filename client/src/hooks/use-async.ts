/**
 * Loads an API resource and re-fetches it whenever the screen regains
 * focus, so data stays fresh after the user navigates away and back.
 */

import { useFocusEffect } from 'expo-router';
import { useCallback, useRef, useState } from 'react';

interface AsyncState<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
  reload: () => Promise<void>;
}

export function useApiResource<T>(
  loader: () => Promise<T>,
  deps: React.DependencyList = [],
): AsyncState<T> {
  const [data, setData] = useState<T | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const loadedOnce = useRef(false);

  // The loader closes over `deps`; rebuild it only when those change.
  // eslint-disable-next-line react-hooks/exhaustive-deps
  const boundLoader = useCallback(loader, deps);

  const reload = useCallback(async () => {
    if (!loadedOnce.current) {
      setLoading(true);
    }
    setError(null);
    try {
      setData(await boundLoader());
      loadedOnce.current = true;
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Something went wrong.');
    } finally {
      setLoading(false);
    }
  }, [boundLoader]);

  useFocusEffect(
    useCallback(() => {
      reload();
    }, [reload]),
  );

  return { data, loading, error, reload };
}
