/**
 * App language (English / Spanish). The choice persists across launches
 * and every screen reads its text through `t()`.
 */

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import { setAppLanguage } from '@/lib/api';
import { LANGUAGE_STORAGE_KEY } from '@/constants/config';
import { getItem, setItem } from '@/lib/storage';
import { type Language, translate } from '@/lib/translations';

type Translate = (
  key: string,
  params?: Record<string, string | number>,
  fallback?: string,
) => string;

interface LanguageContextValue {
  language: Language;
  setLanguage: (language: Language) => void;
  t: Translate;
}

const LanguageContext = createContext<LanguageContextValue | undefined>(undefined);

export function LanguageProvider({ children }: { children: React.ReactNode }) {
  const [language, setLanguageState] = useState<Language>('en');

  // Keep the API client's language header in sync so the server can return
  // translated job details + chat for this language (issue #56).
  useEffect(() => {
    setAppLanguage(language);
  }, [language]);

  // Restore the saved language on launch.
  useEffect(() => {
    let active = true;
    (async () => {
      const saved = await getItem(LANGUAGE_STORAGE_KEY);
      if (active && (saved === 'en' || saved === 'es')) {
        setLanguageState(saved);
      }
    })();
    return () => {
      active = false;
    };
  }, []);

  const setLanguage = useCallback((next: Language) => {
    setLanguageState(next);
    setItem(LANGUAGE_STORAGE_KEY, next);
  }, []);

  const t = useCallback<Translate>(
    (key, params, fallback) => translate(language, key, params, fallback),
    [language],
  );

  const value = useMemo<LanguageContextValue>(
    () => ({ language, setLanguage, t }),
    [language, setLanguage, t],
  );

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>;
}

export function useLanguage(): LanguageContextValue {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage must be used within a LanguageProvider');
  }
  return context;
}
