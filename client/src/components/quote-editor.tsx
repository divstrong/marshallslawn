import { useRouter } from 'expo-router';
import { useCallback, useEffect, useMemo, useState } from 'react';
import {
  FlatList,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import {
  Button,
  Card,
  ErrorState,
  LoadingState,
  ScreenHeader,
  SectionLabel,
  TextField,
} from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { api } from '@/lib/api';
import { formatMoney } from '@/lib/format';
import type { Customer, Property, QuoteLineItemDraft, QuoteStatus } from '@/lib/types';

const STATUS_OPTIONS: QuoteStatus[] = ['draft', 'sent', 'accepted', 'declined'];

function emptyItem(): QuoteLineItemDraft {
  return { description: '', quantity: '1', unit_price: '' };
}

function toNumber(value: string): number {
  const parsed = parseFloat(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

/**
 * Create / edit form for a quote. Passing `quoteId` loads an existing
 * quote into the form; omitting it starts a blank quote.
 */
export function QuoteEditor({ quoteId }: { quoteId?: number }) {
  const router = useRouter();
  const { t } = useLanguage();
  const isEdit = quoteId !== undefined;

  const [customer, setCustomer] = useState<Customer | null>(null);
  const [properties, setProperties] = useState<Property[]>([]);
  const [propertyId, setPropertyId] = useState<number | null>(null);
  const [status, setStatus] = useState<QuoteStatus>('draft');
  const [notes, setNotes] = useState('');
  const [lineItems, setLineItems] = useState<QuoteLineItemDraft[]>([emptyItem()]);

  const [loading, setLoading] = useState(isEdit);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);

  const loadProperties = useCallback(async (customerId: number) => {
    try {
      setProperties(await api.customerProperties(customerId));
    } catch {
      setProperties([]);
    }
  }, []);

  // Hydrate the form when editing.
  const hydrate = useCallback(async () => {
    if (!isEdit) return;
    setLoading(true);
    setLoadError(null);
    try {
      const quote = await api.quote(quoteId);
      setCustomer(quote.customer);
      setPropertyId(quote.property?.id ?? null);
      setStatus((quote.status as QuoteStatus) ?? 'draft');
      setNotes(quote.notes ?? '');
      setLineItems(
        quote.line_items.length > 0
          ? quote.line_items.map((item) => ({
              description: item.description,
              quantity: String(item.quantity),
              unit_price: String(item.unit_price),
            }))
          : [emptyItem()],
      );
      if (quote.customer) {
        await loadProperties(quote.customer.id);
      }
    } catch (e) {
      setLoadError(e instanceof Error ? e.message : t('common.somethingWrong'));
    } finally {
      setLoading(false);
    }
  }, [isEdit, quoteId, loadProperties, t]);

  useEffect(() => {
    hydrate();
  }, [hydrate]);

  const total = useMemo(
    () =>
      lineItems.reduce(
        (sum, item) => sum + toNumber(item.quantity) * toNumber(item.unit_price),
        0,
      ),
    [lineItems],
  );

  const pickCustomer = useCallback(
    (selected: Customer) => {
      setCustomer(selected);
      setPropertyId(null);
      setProperties([]);
      setPickerOpen(false);
      loadProperties(selected.id);
    },
    [loadProperties],
  );

  const updateItem = (index: number, patch: Partial<QuoteLineItemDraft>) => {
    setLineItems((items) => items.map((item, i) => (i === index ? { ...item, ...patch } : item)));
  };

  const removeItem = (index: number) => {
    setLineItems((items) => (items.length > 1 ? items.filter((_, i) => i !== index) : items));
  };

  const save = useCallback(async () => {
    if (!customer) {
      setFormError(t('quote.errCustomer'));
      return;
    }
    const items = lineItems
      .filter((item) => item.description.trim().length > 0)
      .map((item) => ({
        description: item.description.trim(),
        quantity: toNumber(item.quantity),
        unit_price: toNumber(item.unit_price),
      }));
    if (items.length === 0) {
      setFormError(t('quote.errLineItem'));
      return;
    }

    setFormError(null);
    setSaving(true);
    try {
      const payload = {
        customer_id: customer.id,
        property_id: propertyId,
        status,
        notes: notes.trim() || null,
        line_items: items,
      };
      if (isEdit) {
        await api.updateQuote(quoteId, payload);
      } else {
        await api.createQuote(payload);
      }
      router.back();
    } catch (e) {
      setFormError(e instanceof Error ? e.message : t('common.somethingWrong'));
      setSaving(false);
    }
  }, [customer, lineItems, propertyId, status, notes, isEdit, quoteId, router, t]);

  if (loading) {
    return (
      <View style={styles.screen}>
        <ScreenHeader title={t('quote.editTitle')} onBack={() => router.back()} />
        <LoadingState label={t('quote.loading')} />
      </View>
    );
  }

  if (loadError) {
    return (
      <View style={styles.screen}>
        <ScreenHeader title={t('quote.editTitle')} onBack={() => router.back()} />
        <ErrorState message={loadError} onRetry={hydrate} />
      </View>
    );
  }

  return (
    <View style={styles.screen}>
      <ScreenHeader
        title={isEdit ? t('quote.editTitle') : t('quote.newTitle')}
        subtitle={t('quote.estimator')}
        onBack={() => router.back()}
      />

      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView contentContainerStyle={styles.body} keyboardShouldPersistTaps="handled">
          {formError ? (
            <View style={styles.errorBox}>
              <Icon name="alert-circle" size={16} color={AppColors.danger} />
              <Text style={styles.errorText}>{formError}</Text>
            </View>
          ) : null}

          {/* Customer */}
          <View style={styles.section}>
            <SectionLabel>{t('quote.customer')}</SectionLabel>
            <Pressable onPress={() => setPickerOpen(true)}>
              <Card style={styles.selectRow}>
                <View style={styles.flex}>
                  <Text style={customer ? styles.selectValue : styles.selectPlaceholder}>
                    {customer ? customer.name : t('quote.selectCustomer')}
                  </Text>
                  {customer?.city ? (
                    <Text style={styles.selectMeta}>
                      {[customer.city, customer.state].filter(Boolean).join(', ')}
                    </Text>
                  ) : null}
                </View>
                <Icon name="chevron-forward" size={18} color={AppColors.textFaint} />
              </Card>
            </Pressable>
          </View>

          {/* Property */}
          {customer && properties.length > 0 ? (
            <View style={styles.section}>
              <SectionLabel>{t('quote.property')}</SectionLabel>
              <View style={styles.chips}>
                {properties.map((property) => {
                  const active = property.id === propertyId;
                  return (
                    <Pressable
                      key={property.id}
                      onPress={() => setPropertyId(active ? null : property.id)}
                      style={[styles.chip, active && styles.chipActive]}
                    >
                      <Text style={[styles.chipText, active && styles.chipTextActive]}>
                        {property.address ?? property.full_address}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
            </View>
          ) : null}

          {/* Status */}
          <View style={styles.section}>
            <SectionLabel>{t('quote.status')}</SectionLabel>
            <View style={styles.chips}>
              {STATUS_OPTIONS.map((option) => {
                const active = option === status;
                return (
                  <Pressable
                    key={option}
                    onPress={() => setStatus(option)}
                    style={[styles.chip, active && styles.chipActive]}
                  >
                    <Text style={[styles.chipText, active && styles.chipTextActive]}>
                      {t(`status.${option}`)}
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          </View>

          {/* Line items */}
          <View style={styles.section}>
            <SectionLabel>{t('quote.lineItems')}</SectionLabel>
            {lineItems.map((item, index) => (
              <Card key={index} style={styles.itemCard}>
                <View style={styles.itemHeader}>
                  <Text style={styles.itemNumber}>{t('quote.item', { n: index + 1 })}</Text>
                  {lineItems.length > 1 ? (
                    <Pressable onPress={() => removeItem(index)} hitSlop={8}>
                      <Icon name="trash-outline" size={18} color={AppColors.danger} />
                    </Pressable>
                  ) : null}
                </View>
                <TextField
                  placeholder={t('quote.description')}
                  value={item.description}
                  onChangeText={(text) => updateItem(index, { description: text })}
                  style={styles.noMargin}
                />
                <View style={styles.itemRow}>
                  <TextField
                    label={t('quote.qty')}
                    value={item.quantity}
                    onChangeText={(text) => updateItem(index, { quantity: text })}
                    keyboardType="decimal-pad"
                    style={styles.noMargin}
                  />
                  <View style={styles.itemSpacer} />
                  <TextField
                    label={t('quote.unitPrice')}
                    value={item.unit_price}
                    onChangeText={(text) => updateItem(index, { unit_price: text })}
                    keyboardType="decimal-pad"
                    placeholder="0.00"
                    style={styles.noMargin}
                  />
                </View>
                <Text style={styles.itemTotal}>
                  {t('quote.lineTotal', {
                    amount: formatMoney(toNumber(item.quantity) * toNumber(item.unit_price)),
                  })}
                </Text>
              </Card>
            ))}
            <Button
              label={t('quote.addLineItem')}
              icon="add"
              variant="secondary"
              onPress={() => setLineItems((items) => [...items, emptyItem()])}
            />
          </View>

          {/* Notes */}
          <View style={styles.section}>
            <SectionLabel>{t('quote.notes')}</SectionLabel>
            <TextField
              placeholder={t('quote.notesPlaceholder')}
              value={notes}
              onChangeText={setNotes}
              multiline
              style={styles.notesInput}
            />
          </View>

          {/* Total + save */}
          <Card style={styles.totalCard}>
            <Text style={styles.totalLabel}>{t('quote.total')}</Text>
            <Text style={styles.totalValue}>{formatMoney(total)}</Text>
          </Card>

          <Button
            label={isEdit ? t('quote.saveChanges') : t('quote.create')}
            icon="checkmark"
            loading={saving}
            onPress={save}
          />
        </ScrollView>
      </KeyboardAvoidingView>

      <CustomerPicker
        visible={pickerOpen}
        onClose={() => setPickerOpen(false)}
        onSelect={pickCustomer}
      />
    </View>
  );
}

/* -------------------------------------------------------------------------- */
/* Customer picker modal                                                      */
/* -------------------------------------------------------------------------- */

interface CustomerPickerProps {
  visible: boolean;
  onClose: () => void;
  onSelect: (customer: Customer) => void;
}

function CustomerPicker({ visible, onClose, onSelect }: CustomerPickerProps) {
  const { t } = useLanguage();
  const insets = useSafeAreaInsets();
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Customer[]>([]);
  const [searching, setSearching] = useState(false);

  // Debounced search whenever the picker is open.
  useEffect(() => {
    if (!visible) return;
    let active = true;
    setSearching(true);
    const handle = setTimeout(async () => {
      try {
        const customers = await api.customers(query.trim() || undefined);
        if (active) setResults(customers);
      } catch {
        if (active) setResults([]);
      } finally {
        if (active) setSearching(false);
      }
    }, 300);
    return () => {
      active = false;
      clearTimeout(handle);
    };
  }, [query, visible]);

  return (
    <Modal visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={[styles.modal, { paddingTop: insets.top + Spacing.three }]}>
        <View style={styles.modalHeader}>
          <Text style={styles.modalTitle}>{t('quote.pickerTitle')}</Text>
          <Pressable onPress={onClose} hitSlop={10}>
            <Icon name="close" size={24} color={AppColors.text} />
          </Pressable>
        </View>
        <View style={styles.modalSearch}>
          <TextField
            placeholder={t('quote.searchCustomers')}
            value={query}
            onChangeText={setQuery}
            autoCapitalize="none"
            autoFocus
            style={styles.noMargin}
          />
        </View>
        <FlatList
          data={results}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.modalList}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => onSelect(item)}
              style={({ pressed }) => [styles.customerRow, pressed && styles.pressed]}
            >
              <View style={styles.flex}>
                <Text style={styles.customerName}>{item.name}</Text>
                {item.city ? (
                  <Text style={styles.customerMeta}>
                    {[item.city, item.state].filter(Boolean).join(', ')}
                  </Text>
                ) : null}
              </View>
              <Icon name="chevron-forward" size={18} color={AppColors.textFaint} />
            </Pressable>
          )}
          ListEmptyComponent={
            <Text style={styles.modalEmpty}>
              {searching ? t('quote.searching') : t('quote.noCustomers')}
            </Text>
          }
        />
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  flex: {
    flex: 1,
  },
  body: {
    padding: Spacing.four,
    gap: Spacing.three,
    paddingBottom: Spacing.eight,
  },
  section: {
    gap: Spacing.two,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
    backgroundColor: AppColors.dangerSoft,
    borderRadius: Radius.md,
    padding: Spacing.three,
  },
  errorText: {
    flex: 1,
    color: AppColors.danger,
    fontSize: 13,
    fontWeight: '500',
  },
  selectRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  selectValue: {
    fontSize: 15,
    fontWeight: '600',
    color: AppColors.text,
  },
  selectPlaceholder: {
    fontSize: 15,
    color: AppColors.textFaint,
  },
  selectMeta: {
    fontSize: 13,
    color: AppColors.textMuted,
    marginTop: 2,
  },
  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: Spacing.two,
  },
  chip: {
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    borderRadius: Radius.full,
    borderWidth: 1,
    borderColor: AppColors.border,
    backgroundColor: AppColors.surface,
  },
  chipActive: {
    backgroundColor: AppColors.brand,
    borderColor: AppColors.brand,
  },
  chipText: {
    fontSize: 13,
    fontWeight: '600',
    color: AppColors.textMuted,
  },
  chipTextActive: {
    color: AppColors.onBrand,
  },
  itemCard: {
    gap: Spacing.three,
  },
  itemHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  itemNumber: {
    fontSize: 13,
    fontWeight: '700',
    color: AppColors.textMuted,
  },
  itemRow: {
    flexDirection: 'row',
  },
  itemSpacer: {
    width: Spacing.three,
  },
  itemTotal: {
    fontSize: 13,
    fontWeight: '600',
    color: AppColors.textSecondary,
  },
  noMargin: {
    marginBottom: 0,
  },
  notesInput: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  totalCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  totalLabel: {
    fontSize: 15,
    fontWeight: '700',
    color: AppColors.text,
  },
  totalValue: {
    fontSize: 24,
    fontWeight: '800',
    color: AppColors.brand,
  },
  modal: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.four,
    paddingBottom: Spacing.three,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: AppColors.text,
  },
  modalSearch: {
    paddingHorizontal: Spacing.four,
  },
  modalList: {
    paddingHorizontal: Spacing.four,
    paddingBottom: Spacing.eight,
  },
  customerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: Spacing.three,
    borderBottomWidth: 1,
    borderBottomColor: AppColors.border,
  },
  customerName: {
    fontSize: 15,
    fontWeight: '600',
    color: AppColors.text,
  },
  customerMeta: {
    fontSize: 13,
    color: AppColors.textMuted,
    marginTop: 2,
  },
  modalEmpty: {
    textAlign: 'center',
    color: AppColors.textMuted,
    paddingVertical: Spacing.six,
  },
  pressed: {
    opacity: 0.6,
  },
});
