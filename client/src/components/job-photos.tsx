import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import { Icon } from '@/components/icon';
import { Card } from '@/components/ui';
import { AppColors, Radius, Spacing } from '@/constants/theme';
import { api } from '@/lib/api';
import type { JobMedia } from '@/lib/types';

interface JobPhotosProps {
  jobId: number;
  media: JobMedia[];
  onChanged: () => void | Promise<void>;
}

/** Photo gallery + capture/upload for the job detail screen. */
export function JobPhotos({ jobId, media, onChanged }: JobPhotosProps) {
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
      Alert.alert('Upload', e instanceof Error ? e.message : 'The photo could not be uploaded.');
    } finally {
      setUploading(false);
    }
  };

  const pickFrom = async (source: 'camera' | 'library') => {
    try {
      if (source === 'camera') {
        const permission = await ImagePicker.requestCameraPermissionsAsync();
        if (!permission.granted) {
          Alert.alert('Camera', 'Camera access is needed to take a photo.');
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
      Alert.alert('Photo', e instanceof Error ? e.message : 'Could not open the picker.');
    }
  };

  const addPhoto = () => {
    Alert.alert('Add photo', 'Attach a photo to this job.', [
      { text: 'Take photo', onPress: () => pickFrom('camera') },
      { text: 'Choose from library', onPress: () => pickFrom('library') },
      { text: 'Cancel', style: 'cancel' },
    ]);
  };

  const removePhoto = (item: JobMedia) => {
    Alert.alert('Remove photo', 'Remove this photo from the job?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove',
        style: 'destructive',
        onPress: async () => {
          try {
            await api.deleteJobPhoto(jobId, item.id);
            await onChanged();
          } catch (e) {
            Alert.alert('Remove', e instanceof Error ? e.message : 'Could not remove the photo.');
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
        <Text style={styles.empty}>No photos yet — capture one from the job site.</Text>
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
        <Text style={styles.addText}>{uploading ? 'Uploading…' : 'Add photo'}</Text>
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
