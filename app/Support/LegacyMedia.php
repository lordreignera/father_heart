<?php

namespace App\Support;

class LegacyMedia
{
    /** Resolve only image files beneath the private legacy archive, never a URL or arbitrary path. */
    public function imagePath(?string $reference): ?string
    {
        if (! config('legacy.media_ready')) return null;
        $reference = str_replace('\\', '/', trim($reference ?? ''));
        if (! str_starts_with(strtolower($reference), 'images/') || str_contains($reference, "\0")) return null;
        $root = realpath(config('legacy.media_root'));
        if ($root === false || ! is_dir($root)) return null;
        $path = realpath($root.DIRECTORY_SEPARATOR.substr($reference, 7));
        if ($path === false || ! is_file($path)) return null;
        $prefix = strtolower(rtrim(str_replace('\\', '/', $root), '/').'/');
        if (! str_starts_with(strtolower(str_replace('\\', '/', $path)), $prefix)) return null;
        $info = @getimagesize($path);
        if ($info === false || ! in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) return null;
        return $path;
    }
}
