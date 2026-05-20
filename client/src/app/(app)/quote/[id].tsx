import { useLocalSearchParams } from 'expo-router';

import { QuoteEditor } from '@/components/quote-editor';

export default function EditQuoteScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  return <QuoteEditor quoteId={Number(id)} />;
}
