<?php

namespace Vitebox\LaravelBlog\Support;

/**
 * Turns a YouTube / Vimeo / direct file URL into embeddable information.
 */
class VideoEmbed
{
    /**
     * @return array{provider: string, id: ?string, url: string, embed_url: ?string, thumbnail: ?string}|null
     */
    public static function parse(?string $url): ?array
    {
        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return [
                'provider' => 'youtube',
                'id' => $m[1],
                'url' => $url,
                'embed_url' => 'https://www.youtube-nocookie.com/embed/'.$m[1],
                'thumbnail' => 'https://i.ytimg.com/vi/'.$m[1].'/hqdefault.jpg',
            ];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return [
                'provider' => 'vimeo',
                'id' => $m[1],
                'url' => $url,
                'embed_url' => 'https://player.vimeo.com/video/'.$m[1],
                'thumbnail' => null,
            ];
        }

        return [
            'provider' => 'file',
            'id' => null,
            'url' => $url,
            'embed_url' => null,
            'thumbnail' => null,
        ];
    }

    public static function isSupportedUrl(string $url): bool
    {
        $info = self::parse($url);

        return $info !== null && ($info['provider'] !== 'file' || preg_match('~\.(mp4|webm|mov|m3u8)(\?.*)?$~i', $url));
    }
}
