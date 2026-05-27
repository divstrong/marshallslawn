import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Card } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { useLanguage } from '@/context/language';
import { api } from '@/lib/api';
import type { JobMedia } from '@/lib/types';

interface JobPhotosProps {
  jobId: number;
  media: JobMedia[];
  onChanged: () => void | Promise<void>;
}

/** Photo gallery + capture/upload for the job detail screen. */
export function JobPhotos({ jobId, media, onChanged }: JobPhotosProps) {
  const { t } = useLanguage();
  const [uploading, setUploading] = useState(false);

  const upload = async (asset: ImagePicker.ImagePickerAsset) => {
    setUploading(true);
    try {
      await api.uploadJobPhoto(jobId, {
        uri: asset.uri,
        name: asset.fileName ?? `photo-${Date.now()}.jpg`,
        type: asset.mimeType ?? 'image/jpeg',
      });
      await onChanged();
    } catch (e) {
      Alert.alert(t('job.photos'), e instanceof Error ? e.message : t('common.somethingWrong'));
    } finally {
      setUploading(false);
    }
  };

  const pickFrom = async (source: 'camera' | 'library') => {
    try {
      if (source === 'camera') {
        const permission = await ImagePicker.requestCameraPermissionsAsync();
        if (!permission.granted) {
          Alert.alert(t('job.photos'), t('common.cameraNeeded'));
          return;
        }
        const result = await ImagePicker.launchCameraAsync({ quality: 0.6 });
        if (!result.canceled) {
          await upload(result.assets[0]);
        }
      } else {
        const result = await ImagePicker.launchImageLibraryAsync({
          mediaTypes: ['images'],
          quality: 0.6,
        });
        if (!result.canceled) {
          await upload(result.assets[0]);
        }
      }
    } catch (e) {
      Alert.alert(t('job.photos'), e instanceof Error ? e.message : t('common.somethingWrong'));
    }
  };

  const addPhoto = () => {
    Alert.alert(t('jobPhotos.add'), t('jobPhotos.addMsg'), [
      { text: t('common.takePhoto'), onPress: () => pickFrom('camera') },
      { text: t('common.chooseLibrary'), onPress: () => pickFrom('library') },
      { text: t('common.cancel'), style: 'cancel' },
    ]);
  };

  const removePhoto = (item: JobMedia) => {
    Alert.alert(t('jobPhotos.removeTitle'), t('jobPhotos.removeMsg'), [
      { text: t('common.cancel'), style: 'cancel' },
      {
        text: t('common.remove'),
        style: 'destructive',
        onPress: async () => {
          try {
            await api.deleteJobPhoto(jobId, item.id);
            await onChanged();
          } catch (e) {
            Alert.alert(
              t('jobPhotos.removeTitle'),
              e instanceof Error ? e.message : t('common.somethingWrong'),
            );
          }
        },
      },
    ]);
  };

  return (
    <Card style={styles.card}>
      {media.length > 0 ? (
        <View style={styles.grid}>
          {media.map((item) => (
            <View key={item.id} style={styles.thumbWrap}>
              <Image source={{ uri: item.url }} style={styles.thumb} contentFit="cover" transition={150} />
              <Pressable onPress={() => removePhoto(item)} hitSlop={6} style={styles.remove}>
                <Icon name="close" size={14} color={AppColors.onBrand} />
              </Pressable>
            </View>
          ))}
        </View>
      ) : (
        <Text style={styles.empty}>{t('jobPhotos.empty')}</Text>
      )}

      <Pressable
        onPress={addPhoto}
        disabled={uploading}
        style={({ pressed }) => [styles.addButton, (pressed || uploading) && styles.addPressed]}
      >
        {uploading ? (
          <ActivityIndicator size="small" color={AppColors.brand} />
        ) : (
          <Icon name="camera-outline" size={18} color={AppColors.brand} />
        )}
        <Text style={styles.addText}>{uploading ? t('jobPhotos.uploading') : t('jobPhotos.add')}</Text>
      </Pressable>
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    gap: Spacing.three,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: Spacing.two,
  },
  thumbWrap: {
    width: '31.5%',
    aspectRatio: 1,
  },
  thumb: {
    flex: 1,
    borderRadius: Radius.md,
    backgroundColor: AppColors.surfaceMuted,
  },
  remove: {
    position: 'absolute',
    top: -6,
    right: -6,
    width: 24,
    height: 24,
    borderRadius: Radius.full,
    backgroundColor: AppColors.danger,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: AppColors.surface,
  },
  empty: {
    fontSize: 13,
    color: AppColors.textMuted,
  },
  addButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.two,
    height: 46,
    borderRadius: Radius.md,
    borderWidth: 1,
    borderColor: AppColors.brand,
    borderStyle: 'dashed',
  },
  addPressed: {
    opacity: 0.6,
  },
  addText: {
    fontSize: 14,
    fontWeight: '700',
    color: AppColors.brand,
  },
});
