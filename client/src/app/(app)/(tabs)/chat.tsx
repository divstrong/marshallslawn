import * as DocumentPicker from 'expo-document-picker';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Linking,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import { MediaViewer, type MediaSource } from '@/components/media-viewer';
import { EmptyState, ErrorState, LoadingState, ScreenHeader } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useChat } from '@/context/chat';
import { useLanguage } from '@/context/language';
import { useApiResource } from '@/hooks/use-async';
import { api } from '@/lib/api';
import { formatTime } from '@/lib/format';
import { linkify } from '@/lib/linkify';
import type { ChatMessage } from '@/lib/types';

type PickMode = 'camera-photo' | 'camera-video' | 'library';

export default function ChatScreen() {
  const { refresh: refreshBadge } = useChat();
  const { t } = useLanguage();
  const insets = useSafeAreaInsets();

  const { data, loading, error, reload } = useApiResource(() => api.chat(), []);

  const [text, setText] = useState('');
  const [sending, setSending] = useState(false);
  const [viewer, setViewer] = useState<MediaSource | null>(null);

  // Poll the thread every 5s while the chat tab is focused.
  useFocusEffect(
    useCallback(() => {
      const timer = setInterval(() => reload(), 5000);
      return () => clearInterval(timer);
    }, [reload]),
  );

  // Loading the thread marks office messages read — clear the tab badge.
  useEffect(() => {
    if (data) {
      refreshBadge();
    }
  }, [data, refreshBadge]);

  const messages = data ?? [];

  const sendText = useCallback(async () => {
    const body = text.trim();
    if (!body || sending) {
      return;
    }
    setSending(true);
    try {
      await api.sendChatMessage({ body });
      setText('');
      await reload();
    } catch (e) {
      Alert.alert(t('chat.title'), e instanceof Error ? e.message : t('common.somethingWrong'));
    } finally {
      setSending(false);
    }
  }, [text, sending, reload, t]);

  const sendAttachment = useCallback(
    async (file: { uri: string; name: string; type: string }) => {
      setSending(true);
      try {
        await api.sendChatMessage({ body: text.trim() || undefined, file });
        setText('');
        await reload();
      } catch (e) {
        Alert.alert(t('chat.title'), e instanceof Error ? e.message : t('common.somethingWrong'));
      } finally {
        setSending(false);
      }
    },
    [text, reload, t],
  );

  const pick = useCallback(
    async (mode: PickMode) => {
      try {
        let result: ImagePicker.ImagePickerResult;
        if (mode === 'library') {
          result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ['images', 'videos'],
            quality: 0.6,
          });
        } else {
          const permission = await ImagePicker.requestCameraPermissionsAsync();
          if (!permission.granted) {
            Alert.alert(t('chat.title'), t('common.cameraNeeded'));
            return;
          }
          result = await ImagePicker.launchCameraAsync({
            mediaTypes: mode === 'camera-video' ? ['videos'] : ['images'],
            quality: 0.6,
          });
        }
        if (result.canceled) {
          return;
        }
        const asset = result.assets[0];
        const isVideo = asset.type === 'video';
        await sendAttachment({
          uri: asset.uri,
          name: asset.fileName ?? (isVideo ? `video-${Date.now()}.mp4` : `photo-${Date.now()}.jpg`),
          type: asset.mimeType ?? (isVideo ? 'video/mp4' : 'image/jpeg'),
        });
      } catch (e) {
        Alert.alert(t('chat.title'), e instanceof Error ? e.message : t('common.somethingWrong'));
      }
    },
    [sendAttachment, t],
  );

  const pickFile = useCallback(async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: '*/*',
        copyToCacheDirectory: true,
      });
      if (result.canceled) {
        return;
      }
      const asset = result.assets[0];
      await sendAttachment({
        uri: asset.uri,
        name: asset.name ?? `file-${Date.now()}`,
        type: asset.mimeType ?? 'application/octet-stream',
      });
    } catch (e) {
      Alert.alert(t('chat.title'), e instanceof Error ? e.message : t('common.somethingWrong'));
    }
  }, [sendAttachment, t]);

  const openAttachMenu = useCallback(() => {
    Alert.alert(t('chat.addTitle'), t('chat.addMsg'), [
      { text: t('common.takePhoto'), onPress: () => pick('camera-photo') },
      { text: t('common.recordVideo'), onPress: () => pick('camera-video') },
      { text: t('common.chooseLibrary'), onPress: () => pick('library') },
      { text: t('common.chooseFile'), onPress: () => pickFile() },
      { text: t('common.cancel'), style: 'cancel' },
    ]);
  }, [pick, pickFile, t]);

  const canSend = text.trim().length > 0 && !sending;

  return (
    <KeyboardAvoidingView
      style={styles.screen}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScreenHeader title={t('chat.title')} subtitle={t('chat.subtitle')} />

      {loading && !data ? (
        <LoadingState label={t('chat.loading')} />
      ) : error && !data ? (
        <ErrorState message={error} onRetry={reload} />
      ) : messages.length === 0 ? (
        // Rendered as a sibling (not the FlatList's ListEmptyComponent) so it
        // isn't affected by the inverted list's flip transform.
        <View style={styles.empty}>
          <EmptyState
            icon="chatbubbles-outline"
            title={t('chat.emptyTitle')}
            message={t('chat.emptyMsg')}
          />
        </View>
      ) : (
        <FlatList
          data={messages}
          inverted
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          renderItem={({ item }) => <ChatBubble message={item} onOpenMedia={setViewer} />}
        />
      )}

      <View style={[styles.composer, { paddingBottom: Math.max(insets.bottom, Spacing.three) }]}>
        <Pressable onPress={openAttachMenu} disabled={sending} hitSlop={8} style={styles.attach}>
          <Icon name="add-circle" size={30} color={AppColors.brand} />
        </Pressable>
        <TextInput
          style={styles.input}
          placeholder={t('chat.placeholder')}
          placeholderTextColor={AppColors.textFaint}
          value={text}
          onChangeText={setText}
          multiline
        />
        <Pressable
          onPress={sendText}
          disabled={!canSend}
          hitSlop={8}
          style={[styles.send, !canSend && styles.sendDisabled]}
        >
          {sending ? (
            <ActivityIndicator size="small" color={AppColors.onBrand} />
          ) : (
            <Icon name="arrow-up" size={20} color={AppColors.onBrand} />
          )}
        </Pressable>
      </View>

      <MediaViewer source={viewer} onClose={() => setViewer(null)} />
    </KeyboardAvoidingView>
  );
}

function ChatBubble({
  message,
  onOpenMedia,
}: {
  message: ChatMessage;
  onOpenMedia: (source: MediaSource) => void;
}) {
  const { t } = useLanguage();
  const mine = message.sender === 'foreman';
  const attachment = message.attachment;

  return (
    <View style={[styles.row, mine ? styles.rowMine : styles.rowTheirs]}>
      <View style={[styles.bubble, mine ? styles.bubbleMine : styles.bubbleTheirs]}>
        {attachment && attachment.type === 'file' ? (
          <Pressable
            onPress={() => Linking.openURL(attachment.url)}
            style={[styles.fileRow, message.body ? styles.attachmentSpaced : null]}
          >
            <Icon name="document" size={22} color={mine ? '#ffffff' : AppColors.brand} />
            <Text
              numberOfLines={1}
              style={[styles.fileName, mine ? styles.textMine : styles.textTheirs]}
            >
              {attachment.name ?? 'File'}
            </Text>
          </Pressable>
        ) : attachment ? (
          <Pressable
            onPress={() => onOpenMedia({ type: attachment.type as MediaSource['type'], url: attachment.url })}
            style={[styles.attachmentWrap, message.body ? styles.attachmentSpaced : null]}
          >
            {attachment.type === 'photo' ? (
              <Image
                source={{ uri: attachment.url }}
                style={styles.attachmentMedia}
                contentFit="cover"
                transition={150}
              />
            ) : (
              <View style={[styles.attachmentMedia, styles.videoTile]}>
                <Icon name="play-circle" size={46} color="#ffffff" />
                <Text style={styles.videoLabel}>{t('chat.playVideo')}</Text>
              </View>
            )}
          </Pressable>
        ) : null}

        {message.body ? (
          <Text style={[styles.bubbleText, mine ? styles.textMine : styles.textTheirs]}>
            {linkify(message.body, mine ? styles.linkMine : styles.linkTheirs)}
          </Text>
        ) : null}

        <Text style={[styles.meta, mine ? styles.metaMine : styles.metaTheirs]}>
          {mine ? '' : `${message.sender_name} · `}
          {formatTime(message.created_at)}
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: AppColors.background,
  },
  list: {
    padding: Spacing.four,
    gap: Spacing.three,
    flexGrow: 1,
  },
  empty: {
    flex: 1,
  },
  row: {
    flexDirection: 'row',
  },
  rowMine: {
    justifyContent: 'flex-end',
  },
  rowTheirs: {
    justifyContent: 'flex-start',
  },
  bubble: {
    maxWidth: '84%',
    borderRadius: Radius.lg,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
  },
  bubbleMine: {
    backgroundColor: AppColors.brand,
    borderBottomRightRadius: Radius.sm / 2,
  },
  bubbleTheirs: {
    backgroundColor: AppColors.surface,
    borderWidth: 1,
    borderColor: AppColors.border,
    borderBottomLeftRadius: Radius.sm / 2,
  },
  bubbleText: {
    fontSize: 15,
    lineHeight: 21,
  },
  textMine: {
    color: AppColors.onBrand,
  },
  textTheirs: {
    color: AppColors.text,
  },
  linkMine: {
    color: '#ffffff',
    textDecorationLine: 'underline',
  },
  linkTheirs: {
    color: AppColors.brand,
    textDecorationLine: 'underline',
  },
  attachmentWrap: {
    borderRadius: Radius.md,
    overflow: 'hidden',
  },
  attachmentSpaced: {
    marginBottom: Spacing.two,
  },
  fileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
    maxWidth: 224,
  },
  fileName: {
    flexShrink: 1,
    fontSize: 14,
    fontWeight: '600',
    textDecorationLine: 'underline',
  },
  attachmentMedia: {
    width: 224,
    height: 168,
    borderRadius: Radius.md,
    backgroundColor: AppColors.surfaceMuted,
  },
  videoTile: {
    backgroundColor: '#1f2937',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.one,
  },
  videoLabel: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '600',
  },
  meta: {
    fontSize: 11,
    marginTop: 4,
  },
  metaMine: {
    color: 'rgba(255,255,255,0.75)',
    textAlign: 'right',
  },
  metaTheirs: {
    color: AppColors.textFaint,
  },
  composer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: Spacing.two,
    paddingHorizontal: Spacing.three,
    paddingTop: Spacing.three,
    backgroundColor: AppColors.surface,
    borderTopWidth: 1,
    borderTopColor: AppColors.border,
  },
  attach: {
    paddingBottom: 5,
  },
  input: {
    flex: 1,
    minHeight: 40,
    maxHeight: 120,
    backgroundColor: AppColors.surfaceMuted,
    borderRadius: Radius.lg,
    paddingHorizontal: Spacing.three,
    paddingTop: Spacing.two,
    paddingBottom: Spacing.two,
    fontSize: 15,
    color: AppColors.text,
  },
  send: {
    width: 40,
    height: 40,
    borderRadius: Radius.full,
    backgroundColor: AppColors.brand,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendDisabled: {
    backgroundColor: AppColors.textFaint,
  },
});
