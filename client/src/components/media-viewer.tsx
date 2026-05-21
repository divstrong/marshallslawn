import { Image } from 'expo-image';
import { useVideoPlayer, VideoView } from 'expo-video';
import { Modal, Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Icon } from '@/components/icon';
import { Radius } from '@/constants/theme';

export type MediaSource = { type: 'photo' | 'video'; url: string };

/** Full-screen viewer for a chat photo or video. */
export function MediaViewer({
  source,
  onClose,
}: {
  source: MediaSource | null;
  onClose: () => void;
}) {
  const insets = useSafeAreaInsets();

  return (
    <Modal visible={source !== null} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.backdrop}>
        {source?.type === 'photo' ? (
          <Image source={{ uri: source.url }} style={styles.media} contentFit="contain" />
        ) : null}
        {source?.type === 'video' ? <VideoScreen uri={source.url} /> : null}

        <Pressable
          onPress={onClose}
          hitSlop={12}
          style={[styles.close, { top: insets.top + 8 }]}
        >
          <Icon name="close" size={26} color="#ffffff" />
        </Pressable>
      </View>
    </Modal>
  );
}

function VideoScreen({ uri }: { uri: string }) {
  const player = useVideoPlayer(uri, (instance) => {
    instance.loop = false;
    instance.play();
  });

  return (
    <VideoView player={player} style={styles.media} contentFit="contain" nativeControls />
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.92)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  media: {
    width: '100%',
    height: '82%',
  },
  close: {
    position: 'absolute',
    right: 16,
    width: 40,
    height: 40,
    borderRadius: Radius.full,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
});
