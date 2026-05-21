/** Renders message text with tappable http(s) links. */

import { Text, type TextStyle } from 'react-native';

import { openUrl } from '@/lib/links';

const URL_SPLIT = /(https?:\/\/[^\s]+)/gi;
const IS_URL = /^https?:\/\//i;

export function linkify(text: string, linkStyle: TextStyle): React.ReactNode {
  return text.split(URL_SPLIT).map((part, index) => {
    if (IS_URL.test(part)) {
      // Trim a trailing sentence punctuation mark from the tappable target.
      const trailing = /[.,);!?]+$/.exec(part);
      const url = trailing ? part.slice(0, part.length - trailing[0].length) : part;
      return (
        <Text key={index} style={linkStyle} onPress={() => openUrl(url)}>
          {part}
        </Text>
      );
    }
    return part;
  });
}
